<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\LoggerAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session;
use Common\Service\OneLogin\OneLoginService;
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
        OneLoginService $oneLoginService,
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $authParams = $request->getQueryParams();

        $session      = $this->getSession($request, SessionMiddleware::SESSION_ATTRIBUTE);
        $authSession  = $session->get(OneLoginService::OIDC_AUTH_INTERFACE);
        $sessionState = $authSession['state'];
        $ui_locale    = $authSession['customs']['ui_locale'];

        if (!array_key_exists('state', $authParams)) {
            throw new RuntimeException('Required parameters not passed for authentication', 500);
        }

        if ($sessionState !== $authParams['state']) {
            throw new RuntimeException('Session state does not match redirect state', 500);
        }

        //TODO: Content and Welsh translations
        ////http://localhost:9002/home/login?error=invalid_request&error_description=Unsupported%20response&state=
        if (array_key_exists('error', $authParams)) {
            switch ($authParams['error']) {
                case 'temporarily_unavailable':
                    //http://localhost:9002/home/login?error=temporarily_unavailable&error_description=Unsupported%20response&state=
                    return $this->redirectToRoute('home', [], [
                        'error' => 'temporarily_unavailable',
                    ]);
                case 'access_denied':
                    //http://localhost:9002/home/login?error=access_denied&error_description=Unsupported%20response&state=
                    return $this->redirectToRoute('home', [], [
                        'error' => 'access_denied',
                    ]);
                default:
                    $this->logger->notice('User attempted to login via OneLogin however there was an error');
                    throw new RuntimeException('Error returned from OneLogin', 500);
            }
        }

        if (!array_key_exists('code', $authParams)) {
            throw new RuntimeException('Required parameters not passed for authentication', 500);
        }

        return new HtmlResponse('<h1>Hello World</h1>');
    }
}
