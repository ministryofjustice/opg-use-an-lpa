<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Form\CookieConsent;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Helper\UrlHelper;
use Common\Service\Url\UrlValidityCheckService;

/**
 * Class CookiesPageHandler
 * @package Viewer\Handler
 */
class CookiesPageHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;

    /**
     * @var UrlValidityCheckService
     */
    private $urlValidityCheckService;

    const COOKIE_POLICY_NAME = 'cookie_policy';
    const SEEN_COOKIE_NAME   = 'seen_cookie_message';

    /**
     * CreateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UrlValidityCheckService $urlValidityCheckService
    )
    {
        parent::__construct($renderer, $urlHelper);
        $this->urlValidityCheckService = $urlValidityCheckService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new CookieConsent($this->getCsrfGuard($request));

        if ($request->getMethod() == 'POST') {
            return $this->handlePost($request);
        }

        $cookies = $request->getCookieParams();

        $usageCookies = 'no';
        if (array_key_exists(self::COOKIE_POLICY_NAME, $cookies)) {
            $cookiePolicy = json_decode($cookies[self::COOKIE_POLICY_NAME], true);
            $usageCookies = $cookiePolicy['usage'] === true ? 'yes' : 'no';
        }
        $form->get('usageCookies')->setValue($usageCookies);

        $cookiesPageReferer = $request->getHeaders()['referer'];
        var_dump($cookiesPageReferer);
        die;

        $validUrl = $this->urlValidityCheckService->isValid($cookiesPageReferer[0]);

        $form->get('referer')->setValue($validUrl ? $cookiesPageReferer[0] : null);

        return new HtmlResponse($this->renderer->render('partials::cookies', [
            'form' => $form
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $form = new CookieConsent($this->getCsrfGuard($request));

        $cookies = $request->getCookieParams();
        $form->setData($request->getParsedBody());

        $isValidRefererRoute = $this->urlValidityCheckService->checkRefererRouteValid($refererRoute = $form->get('referer')->getValue());

        // After setting cookies settings user is taken where they were previously after validating the referer route
        if ($isValidRefererRoute) {
            $response = new RedirectResponse($isValidRefererRoute ? $refererRoute : $this->urlHelper->generate('home'));
        }

        if (array_key_exists(self::COOKIE_POLICY_NAME, $cookies)) {
            try {
                $cookiePolicy = json_decode($cookies[self::COOKIE_POLICY_NAME], true);
            } catch (\Exception $e) {
                return $response;
            }

            $cookiePolicy['usage'] = $form->get('usageCookies')->getValue() === 'yes' ? true : false;
            $response = FigResponseCookies::set($response,
                SetCookie::create(self::COOKIE_POLICY_NAME, json_encode($cookiePolicy))
                    ->withHttpOnly(false)
                    ->withExpires(new \DateTime('+365 days'))
                    ->withPath('/')
            );

            $response = FigResponseCookies::set($response,
                SetCookie::create(self::SEEN_COOKIE_NAME, "true")
                    ->withHttpOnly(false)
                    ->withExpires(new \DateTime('+30 days'))
                    ->withPath('/')
            );
        }
        return $response;
    }
}
