<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\PasswordResetRequest;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Service\Notify\NotifyService;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class PasswordResetRequestPageHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private UserService $userService,
        private ServerUrlHelper $serverUrlHelper,
        private NotifyService $notifyService,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new PasswordResetRequest($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $resetToken = $this->userService->requestPasswordReset($data['email']);

                    $passwordResetPath = $this->urlHelper->generate('password-reset-token', [
                        'token' => $resetToken,
                    ]);

                    $passwordResetUrl = $this->serverUrlHelper->generate($passwordResetPath);

                    if (!empty($data['forced'])) {
                        $this->notifyService->sendEmailToUser(
                            NotifyService::FORCE_PASSWORD_RESET_EMAIL_TEMPLATE,
                            $data['email'],
                            passwordResetUrl: $passwordResetUrl
                        );
                    } else {
                        $this->notifyService->sendEmailToUser(
                            NotifyService::PASSWORD_RESET_EMAIL_TEMPLATE,
                            $data['email'],
                            passwordResetUrl: $passwordResetUrl
                        );
                    }
                } catch (ApiException $ae) {
                    if ($ae->getCode() === StatusCodeInterface::STATUS_NOT_FOUND) {
                        $this->notifyService->sendEmailToUser(
                            NotifyService::NO_ACCOUNT_EXISTS_EMAIL_TEMPLATE,
                            $data['email']
                        );
                    } else {
                        throw $ae;
                    }
                }

                return new HtmlResponse($this->renderer->render('actor::password-reset-request-done', [
                    'email' => strtolower($data['email']),
                ]));
            }
        }

        return new HtmlResponse($this->renderer->render('actor::password-reset-request', [
            'form' => $form,
        ]));
    }
}
