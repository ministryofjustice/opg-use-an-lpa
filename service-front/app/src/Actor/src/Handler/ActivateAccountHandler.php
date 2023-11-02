<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Common\Handler\AbstractHandler;
use Common\Service\Notify\NotifyService;
use Common\Service\User\UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class ActivateAccountHandler extends AbstractHandler
{
    public const ACCOUNT_ACTIVATED_FLASH_MSG = 'account_activated_flash_msg';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private UserService $userService,
        private ServerUrlHelper $serverUrlHelper,
        private TranslatorInterface $translator,
        private NotifyService $notifyService,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $activationToken = $request->getAttribute('token');

        // The ImplicitHeadMiddleware will attach an attribute to the request if it detects a HEAD request
        // We only want to continue with account activation if it is not there.
        if ($request->getAttribute(
            ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
            false
        ) === false
        ) {
            /**
 * @var bool|string $activated
*/
            $activated = $this->userService->activate($activationToken);

            /**
 * @var FlashMessagesInterface $flash
*/
            $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

            if (is_string($activated)) {
                $loginUrl   = $this->urlHelper->generate('login');
                $signInLink = $this->serverUrlHelper->generate($loginUrl);

                $this->notifyService->sendEmailToUser(
                    NotifyService::ACCOUNT_ACTIVATION_CONFIRMATION_EMAIL_TEMPLATE,
                    $activated,
                    signInLink: $signInLink
                );

                $message = $this->translator->translate(
                    'Account activated successfully',
                    [],
                    null,
                    'flashMessage'
                );
                $flash->flash(self::ACCOUNT_ACTIVATED_FLASH_MSG, $message);

                return $this->redirectToRoute('login');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::activate-account-not-found'));
    }
}
