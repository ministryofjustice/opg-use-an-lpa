<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\LoggerAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session;
use Common\Service\Log\EventCodes;
use Common\Service\OneLogin\OneLoginService;
use Facile\OpenIDClient\Session\AuthSession;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @codeCoverageIgnore
 */
class OneLoginCallbackHandler extends AbstractHandler implements LoggerAware, SessionAware
{
    use Logger;
    use Session;

    public function __construct(
        private OneLoginService $oneLoginService,
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    /**
     * @throws ApiException
     * @throws RuntimeException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $authParams = $request->getQueryParams();

        $session     = $this->getSession($request, SessionMiddleware::SESSION_ATTRIBUTE);
        $authSession = AuthSession::fromArray($session->get(OneLoginService::OIDC_AUTH_INTERFACE));
        $ui_locale   = $authSession->getCustoms()['ui_locale'];

        if (array_key_exists('error', $authParams)) {
            $error = $authParams['error'];
            $error === 'temporarily_unavailable' ?
                $this->logger->warning(
                    'User attempted One Login but it is unavailable',
                    ['event_code' => EventCodes::AUTH_ONELOGIN_NOT_AVAILABLE]
                ) :
                $this->logger->notice(
                    'User attempted One Login but received an {error} error',
                    [
                        'error'      => $error,
                        'event_code' => EventCodes::AUTH_ONELOGIN_ERROR,
                    ]
                );

            return match ($error) {
                'access_denied', 'temporarily_unavailable' => $this->redirectToRoute(
                    'home',
                    [],
                    ['error' => $error],
                    $ui_locale === 'cy' ? $ui_locale : null
                ),
                default => throw new RuntimeException(
                    '"' . $error . '" error returned from OneLogin authentication attempt',
                    500
                )
            };
        }

        if (!array_key_exists('code', $authParams) || !array_key_exists('state', $authParams)) {
            throw new RuntimeException('Required parameters not passed for authentication', 500);
        }

        $user = $this->oneLoginService->callback($authParams['code'], $authParams['state'], $authSession);

        $session->set(UserInterface::class, [
            'username' => $user->getIdentity(),
            'roles'    => $user->getRoles(),
            'details'  => $user->getDetails(),
        ]);
        $session->unset(OneLoginService::OIDC_AUTH_INTERFACE);
        $session->regenerate();

        return $this->redirectToRoute(
            'lpa.dashboard',
            [],
            [],
            $ui_locale === 'cy' ? $ui_locale : null
        );
    }
}
