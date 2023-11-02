<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Actor\Form\PasswordChange;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Notify\NotifyService;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use ParagonIE\HiddenString\HiddenString;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class ChangePasswordHandler extends AbstractHandler implements CsrfGuardAware, UserAware, SessionAware
{
    use CsrfGuard;
    use Session;
    use User;

    public const PASSWORD_CHANGED_FLASH_MSG = 'password_changed_flash_msg';

    /**
     * @codeCoverageIgnore
     * @param              TemplateRendererInterface $renderer
     * @param              UrlHelper                 $urlHelper
     * @param              UserService               $userService
     * @param              AuthenticationInterface   $authenticator
     * @param              ServerUrlHelper           $serverUrlHelper
     * @param              TranslatorInterface       $translator
     * @param              NotifyService             $notifyService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private UserService $userService,
        AuthenticationInterface $authenticator,
        private ServerUrlHelper $serverUrlHelper,
        private TranslatorInterface $translator,
        private NotifyService $notifyService,
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new PasswordChange($this->getCsrfGuard($request));

        $user = $this->getUser($request);

        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $this->userService->changePassword(
                        $user->getIdentity(),
                        new HiddenString($data['current_password']),
                        new HiddenString($data['new_password'])
                    );

                    $this->notifyService->sendEmailToUser(
                        NotifyService::PASSWORD_CHANGE_EMAIL_TEMPLATE,
                        $user->getDetail('email')
                    );

                    $session = $this->getSession($request, 'session');
                    $session->unset(UserInterface::class);
                    $session->regenerate();

                    $message = $this->translator->translate(
                        'Password changed successfully',
                        [],
                        null,
                        'flashMessage'
                    );
                    $flash->flash(self::PASSWORD_CHANGED_FLASH_MSG, $message);

                    return $this->redirectToRoute('login');
                } catch (ApiException $e) {
                    if ($e->getCode() === StatusCodeInterface::STATUS_FORBIDDEN) {
                        $form->addErrorMessage(PasswordChange::INVALID_PASSWORD);
                    }
                }
            }
        }

        return new HtmlResponse(
            $this->renderer->render(
                'actor::password-change',
                [
                'user' => $user,
                'form' => $form->prepare(),
                ]
            )
        );
    }
}
