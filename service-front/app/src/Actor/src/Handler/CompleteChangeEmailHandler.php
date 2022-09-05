<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Common\Handler\AbstractHandler;
use Common\Handler\SessionAware;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\User\UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;

/**
 * Class CompleteChangeEmailHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CompleteChangeEmailHandler extends AbstractHandler implements UserAware, SessionAware
{
    use User;
    use Session;

    /** @var UserService */
    private $userService;

    private TranslatorInterface $translator;
    public const NEW_EMAIL_ACTIVATED_FLASH_MSG = 'new_email_activated_flash_msg';

    /**
     * CompleteChangeEmailHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService,
        TranslatorInterface $translator
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
        $this->translator = $translator;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $resetToken = $request->getAttribute('token');

        // The implicitHeadMiddleware will attach an attribute to the request if it detects a HEAD request
        // We only want to continue with email changing if it is not there.
        if (
            $request->getAttribute(
                ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                false
            ) === false
        ) {
            $tokenValid = $this->userService->canResetEmail($resetToken);

            if (!$tokenValid) {
                return new HtmlResponse($this->renderer->render('actor::email-reset-not-found'));
            }

            $this->userService->completeChangeEmail($resetToken);

            $session = $this->getSession($request, 'session');
            $session->unset(UserInterface::class);
            $session->regenerate();
        }

        /** @var FlashMessagesInterface $flash */
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $message = $this->translator->translate(
            'Email address changed successfully',
            [],
            null,
            'flashMessage'
        );
        $flash->flash(self::NEW_EMAIL_ACTIVATED_FLASH_MSG, $message);

        return $this->redirectToRoute('login');
    }
}
