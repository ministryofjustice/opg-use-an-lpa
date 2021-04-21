<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateShareCode;
use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateViewerCodeHandler extends AbstractHandler implements UserAware, CsrfGuardAware
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
        $form = new CreateShareCode($this->getCsrfGuard($request));

        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $validated = $form->getData();

                $codeData = $this->viewerCodeService->createShareCode(
                    $identity,
                    $validated['lpa_token'],
                    $validated['org_name']
                );

                $lpaData = $this->lpaService->getLpaById($identity, $validated['lpa_token']);

                return new HtmlResponse($this->renderer->render('actor::lpa-show-viewercode', [
                    'user'         => $user,
                    'actorToken'   => $validated['lpa_token'],
                    'code'         => $codeData['code'],
                    'expires'      => new DateTimeImmutable($codeData['expires']),
                    'organisation' => $codeData['organisation'],
                    'lpa'          => $lpaData->lpa
                ]));
            }
        }

        // the lpa actor token is either a query parameter or a form value.
        // get it from the form if it doesn't exist as a parameter
        if (isset($request->getQueryParams()['lpa'])) {
            $form->setData(['lpa_token' => $request->getQueryParams()['lpa']]);
        }

        if (is_null($form->get('lpa_token')->getValue())) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $lpaData = $this->lpaService->getLpaById($identity, $form->get('lpa_token')->getValue());

        //UML-1394 TO BE REMOVED IN FUTURE TO SHOW PAGE NOT FOUND WITH APPROPRIATE CONTENT
        if (count($lpaData) === 0) {
            return $this->redirectToRoute('lpa.dashboard');
        }

        return new HtmlResponse($this->renderer->render('actor::lpa-create-viewercode', [
            'user'       => $user,
            'lpa'        => $lpaData->lpa,
            'actorToken' => $form->get('lpa_token')->getValue(),
            'form'       => $form
        ]));
    }
}
