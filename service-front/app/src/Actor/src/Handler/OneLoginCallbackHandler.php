<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\LoggerAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session;
use Common\Service\OneLogin\OneLoginService;
use Facile\OpenIDClient\Session\AuthSession;
use Laminas\Diactoros\Response\HtmlResponse;
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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $authParams = $request->getQueryParams();

        $session     = $this->getSession($request, SessionMiddleware::SESSION_ATTRIBUTE);
        $authSession = AuthSession::fromArray($session->get(OneLoginService::OIDC_AUTH_INTERFACE));
        $ui_locale   = $authSession->getCustoms()['ui_locale'];

        if (array_key_exists('error', $authParams)) {
            $this->logger->notice('User attempted to login via OneLogin however there was an error');
            return match ($authParams['error']) {
                'access_denied' => $this->redirectToRoute(
                    'home',
                    [],
                    ['error' => 'access_denied'],
                    $ui_locale === 'cy' ? $ui_locale : null
                ),
                'temporarily_unavailable' => $this->redirectToRoute(
                    'home',
                    [],
                    ['error' => 'temporarily_unavailable'],
                    $ui_locale === 'cy' ? $ui_locale : null
                ),
                default => throw new RuntimeException('Error returned from OneLogin', 500)
            };
        }

        if (!array_key_exists('code', $authParams) || !array_key_exists('state', $authParams)) {
            throw new RuntimeException('Required parameters not passed for authentication', 500);
        }

        return new HtmlResponse('<h1>Hello World</h1>');

        //TODO: UML-3078
//        $user = $this->oneLoginService->callback($authParams['code'], $authParams['state'], $authSession);
//        //Add user to session
//        if (! is_null($user)) {
//            if (empty($user->getDetail('LastLogin'))) {
//                return $this->redirectToRoute('lpa.add');
//            } else {
//                return $this->redirectToRoute('lpa.dashboard');
//            }
//        }
    }
}
