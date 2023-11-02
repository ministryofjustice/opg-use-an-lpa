<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Actor\Form\PasswordReset;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session;
use Common\Service\Session\EncryptedCookiePersistence;
use Common\Service\User\UserService;
use Laminas\Diactoros\Response\HtmlResponse;
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
class PasswordResetPageHandler extends AbstractHandler implements CsrfGuardAware, SessionAware
{
    use CsrfGuard;
    use Session;

    /**
     * @codeCoverageIgnore
     * @param              TemplateRendererInterface $renderer
     * @param              UrlHelper                 $urlHelper
     * @param              UserService               $userService
     * @param              ServerUrlHelper           $serverUrlHelper
     * @param              TranslatorInterface       $translator
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private UserService $userService,
        private ServerUrlHelper $serverUrlHelper,
        private TranslatorInterface $translator,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    /**
     * Handles a request and produces a response
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new PasswordReset($this->getCsrfGuard($request));

        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $tokenValid = $this->userService->canPasswordReset($request->getAttribute('token'));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                $this->userService->completePasswordReset(
                    $request->getAttribute('token'),
                    new HiddenString($data['password'])
                );

                $this->invalidateSession($request);

                $message = $this->translator->translate(
                    'Password changed successfully',
                    [],
                    null,
                    'flashMessage'
                );
                $flash->flash(ChangePasswordHandler::PASSWORD_CHANGED_FLASH_MSG, $message);

                //  Redirect to the login screen with success flash message
                return $this->redirectToRoute('login');
            }
        }

        if ($tokenValid) {
            return new HtmlResponse(
                $this->renderer->render(
                    'actor::password-reset',
                    [
                    'form' => $form->prepare(),
                    ]
                )
            );
        }

        return new HtmlResponse($this->renderer->render('actor::password-reset-not-found'));
    }

    private function invalidateSession(ServerRequestInterface $request): void
    {
        $session = $this->getSession($request, 'session');

        // Tell the SessionExpiredAttributeAllowlistMiddleware to clean out the session when it's done.
        $session->set(EncryptedCookiePersistence::SESSION_EXPIRED_KEY, true);

        $session->regenerate();
    }
}
