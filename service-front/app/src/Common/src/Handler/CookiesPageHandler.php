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
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Helper\UrlHelper;
use Common\Service\Url\UrlValidityCheckService;

/**
 * Class CookiesPageHandler
 * @package Viewer\Handler
 * @codeCoverageIgnore
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
    ) {
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

        $cookiesPageReferer = $request->getHeaders()['referer'][0];

        $form->get('referer')->setValue($this->urlValidityCheckService->setValidReferer($cookiesPageReferer));

        $routeName = $this->urlHelper->getRouteResult()->getMatchedRouteName();

        return new HtmlResponse($this->renderer->render('partials::cookies', [
            'form'         => $form,
            'routeName'    => $routeName
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $form = new CookieConsent($this->getCsrfGuard($request));

        $cookies = $request->getCookieParams();
        $form->setData($request->getParsedBody());

        $response = new RedirectResponse(
            $this->urlValidityCheckService->setValidReferer($form->get('referer')->getValue())
        );

        if (array_key_exists(self::COOKIE_POLICY_NAME, $cookies)) {
            try {
                $cookiePolicy = json_decode($cookies[self::COOKIE_POLICY_NAME], true);
            } catch (\Exception $e) {
                return $response;
            }

            $cookiePolicy['usage'] = $form->get('usageCookies')->getValue() === 'yes' ? true : false;
            $response = FigResponseCookies::set(
                $response,
                SetCookie::create(self::COOKIE_POLICY_NAME, json_encode($cookiePolicy))
                    ->withHttpOnly(false)
                    ->withExpires(new \DateTime('+365 days'))
                    ->withPath('/')
            );

            $response = FigResponseCookies::set(
                $response,
                SetCookie::create(self::SEEN_COOKIE_NAME, "true")
                    ->withHttpOnly(false)
                    ->withExpires(new \DateTime('+30 days'))
                    ->withPath('/')
            );
        }
        return $response;
    }
}
