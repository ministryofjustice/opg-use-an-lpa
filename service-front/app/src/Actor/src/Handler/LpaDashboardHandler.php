<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Common\Service\Lpa\ViewerCodeService;

class LpaDashboardHandler extends AbstractHandler implements UserAware
{
    use User;

    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * @var ViewerCodeService
     */
    private $viewerCodeService;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        ViewerCodeService $viewerCodeService)
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
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;

        $lpas = $this->lpaService->getLpas($identity);

        if (count($lpas) === 0) {
            return new RedirectResponse($this->urlHelper->generate('lpa.add'));
        }

        foreach ($lpas as $lpaKey => $lpaData) {

        $actorToken = $lpaData['user-lpa-actor-token'];

        $shareCodes = $this->viewerCodeService->getShareCodes(
            $identity,
            $actorToken,
            true
        );

        $lpas[$lpaKey]['activeCodeCount'] = $shareCodes['activeCodeCount'];
    }

        return new HtmlResponse($this->renderer->render('actor::lpa-dashboard', [
            'user' => $this->getUser($request),
            'lpas' => $lpas
        ]));
    }
}