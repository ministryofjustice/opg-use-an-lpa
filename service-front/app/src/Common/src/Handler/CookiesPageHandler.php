<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Form\CookieConsent;
use Common\Handler\Traits\CsrfGuard;
use Common\Service\Url\UrlValidityCheckService;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class CookiesPageHandler
 * @package Viewer\Handler
 * @codeCoverageIgnore
 */
class CookiesPageHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;

    private string $application;

    private UrlValidityCheckService $urlValidityCheckService;

    private const COOKIE_POLICY_NAME = 'cookie_policy';
    private const SEEN_COOKIE_NAME   = 'seen_cookie_message';

    /**
     * CreateAccountHandler constructor.
     *
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UrlValidityCheckService $urlValidityCheckService
     * @param string $application
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UrlValidityCheckService $urlValidityCheckService,
        string $application = 'actor'
    ) {
        parent::__construct($renderer, $urlHelper);
        $this->urlValidityCheckService = $urlValidityCheckService;
        $this->application = $application;
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

        $cookiesPageReferer = $request->getHeaders()['referer'][0] ?? null;

        $form->get('referer')->setValue($this->urlValidityCheckService->setValidReferer($cookiesPageReferer));

        return new HtmlResponse($this->renderer->render('common::cookies', [
            'form'        => $form,
            'application' => $this->application
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $form = new CookieConsent($this->getCsrfGuard($request));

        $form->setData($request->getParsedBody());

        $response = new RedirectResponse(
            $this->urlValidityCheckService->setValidReferer($form->get('referer')->getValue())
        );

        $cookiePolicy = [];
        $cookiePolicy['essential'] = true;
        $cookiePolicy['usage'] = $form->get('usageCookies')->getValue() === 'yes';

        $response = FigResponseCookies::set(
            $response,
            SetCookie::create(self::COOKIE_POLICY_NAME, json_encode($cookiePolicy))
                ->withHttpOnly(false)
                ->withExpires(new \DateTime('+365 days'))
                ->withPath('/')
        );

        return FigResponseCookies::set(
            $response,
            SetCookie::create(self::SEEN_COOKIE_NAME, 'true')
                ->withHttpOnly(false)
                ->withExpires(new \DateTime('+30 days'))
                ->withPath('/')
        );
    }
}
