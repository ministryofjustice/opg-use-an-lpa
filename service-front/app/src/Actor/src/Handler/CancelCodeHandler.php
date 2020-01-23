<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;


/**
 * Class CancelCodeHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CancelCodeHandler extends AbstractHandler implements UserAware
{
    use User;

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
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        ViewerCodeService $viewerCodeService,
        UrlHelper $urlHelper)
    {
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
        $orgToCancel = $request->getQueryParams()['organisation'];
        $actorLpaToken = $request->getQueryParams()['lpa'];

        // var_dump($actorLpaToken);
        // var_dump($identity);

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }
        if (is_null($orgToCancel)) {
            throw new InvalidRequestException('No organisation specified to cancel');
        }

        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;

        $lpa = $this->lpaService->getLpaById($identity, $actorLpaToken);

        $codeData = $this->viewerCodeService->cancelShareCode(
            $identity,
            $actorLpaToken,
            $orgToCancel
        );

        $shareCodes = $this->viewerCodeService->getShareCodes(
            $identity,
            $actorLpaToken,
            false
        );

        foreach ($shareCodes as $key => $code) {

            if ($lpa->getDonor()->getId() == $code['ActorId']) {
                $shareCodes[$key]['CreatedBy'] = $lpa->getDonor()->getFirstname();
            }

            foreach ($lpa->getAttorneys() as $attorney) {
                if ($attorney->getId() == $code['ActorId']) {
                    $shareCodes[$key]['CreatedBy'] = $attorney->getFirstname() . ' ' . $attorney->getSurname();
                }
            }
        }
      //  var_dump($shareCodes);
     //   var_dump("=============");
     //   var_dump($actorLpaToken);
      //  var_dump($user);
      //  var_dump($lpa);
      // die;

//

        return new HtmlResponse($this->renderer->render('actor::check-access-codes', [
        //return new HtmlResponse($this->renderer->render('actor::lpa-dashboard', [
            'actorToken' => $actorLpaToken,
            'user' => $user,
            'lpa' => $lpa,
            'shareCodes' => $shareCodes,
        ]));
    }
}