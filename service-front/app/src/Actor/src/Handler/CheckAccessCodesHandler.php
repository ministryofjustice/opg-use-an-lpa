<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CancelCode;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session, Traits\User, UserAware};
use Common\Service\Lpa\{LpaService, ViewerCodeService};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use DateTime;

class CheckAccessCodesHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use Session;
    use CsrfGuard;

    /**
     * @var ViewerCodeService
     */
    private $viewerCodeService;

    /**
     * @var LpaService
     */
    private $lpaService;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        ViewerCodeService $viewerCodeService
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
        $this->viewerCodeService = $viewerCodeService;
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actorLpaToken = $request->getQueryParams()['lpa'];

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;

        $lpaData = $this->lpaService->getLpaById($identity, $actorLpaToken);

        $shareCodes = $this->viewerCodeService->getShareCodes(
            $identity,
            $actorLpaToken,
            false,
            ViewerCodeService::SORT_ADDED
        );
        foreach ($shareCodes as $key => $code) {

            if (!array_key_exists('Cancelled', $code) || (new DateTime('now') > $code['Expires'])) {
                $form = new CancelCode($this->getCsrfGuard($request));
                $form->setAttribute('action', $this->urlHelper->generate('lpa.confirm-cancel-code'));

                $form->setData([
                    'lpa_token'     => $actorLpaToken,
                    'viewer_code'   => $code['ViewerCode'],
                    'organisation'  => $code['Organisation'],
                ]);

                $shareCodes[$key]['form'] = $form;
            }

            if ($lpaData->lpa->getDonor()->getId() == $code['ActorId']) {
                $shareCodes[$key]['CreatedBy'] = $lpaData->lpa->getDonor()->getFirstname() . ' ' . $lpaData->lpa->getDonor()->getSurname();
            }

            foreach ($lpaData->lpa->getAttorneys() as $attorney) {
                if ($attorney->getId() == $code['ActorId']) {
                    $shareCodes[$key]['CreatedBy'] = $attorney->getFirstname() . ' ' . $attorney->getSurname();
                }
            }
        }

        return new HtmlResponse($this->renderer->render('actor::check-access-codes', [
            'actorToken'    => $actorLpaToken,
            'user'          => $user,
            'lpa'           => $lpaData->lpa,
            'shareCodes'    => $shareCodes,
        ]));
    }
}
