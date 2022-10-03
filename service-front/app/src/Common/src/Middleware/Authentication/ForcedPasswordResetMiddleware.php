<?php

declare(strict_types=1);

namespace Common\Middleware\Authentication;

use Actor\Form\PasswordResetRequest;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ForcedPasswordResetMiddleware implements MiddlewareInterface, CsrfGuardAware, UserAware
{
    use CsrfGuard;
    use User;

    public function __construct(
        private TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        private UrlHelper $urlHelper,
    ) {
        $this->setAuthenticator($authenticator);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user  = $this->getUser($request);
        $email = $user->getDetail('Email');

        if (!$user->getDetail('NeedsReset')) {
            return $handler->handle($request);
        }

        $form = new PasswordResetRequest($this->getCsrfGuard($request));
        $form->setAttribute('action', $this->urlHelper->generate('password-reset'));
        $form->setData(
            [
                'email'         => $email,
                'email_confirm' => $email,
                'forced'        => true,
            ]
        );

        return new HtmlResponse($this->renderer->render(
            'actor::force-password-reset-page',
            [
                'form' => $form,
                'user' => $user,
            ]
        ));
    }
}
