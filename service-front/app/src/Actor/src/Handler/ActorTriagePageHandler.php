<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\Triage;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * @codeCoverageIgnore
 */
class ActorTriagePageHandler extends AbstractHandler implements CsrfGuardAware, UserAware
{
    use CsrfGuard;
    use User;

    private ServerUrlHelper $serverUrlHelper;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new Triage($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        if (! is_null($this->getUser($request))) {
            return $this->redirectToRoute('lpa.dashboard');
        }

        return new HtmlResponse($this->renderer->render('actor::home-page', [
            'form' => $form->prepare(),
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $form        = new Triage($this->getCsrfGuard($request));
        $requestData = $request->getParsedBody();

        $form->setData($requestData);

        if ($form->isValid()) {
            $fromData = $form->getData();

            if ($fromData['triageEntry'] === 'yes') {
                return $this->redirectToRoute('login');
            }
            return $this->redirectToRoute('create-account');
        }

        return new HtmlResponse($this->renderer->render('actor::home-page', [
            'form' => $form->prepare(),
        ]));
    }
}
