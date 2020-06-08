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

/**
 * Class CookiesPageHandler
 * @package Viewer\Handler
 */
class CookiesPageHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;

    const COOKIE_POLICY_NAME = 'cookie_policy';
    const SEEN_COOKIE_NAME   = 'seen_cookie_message';

    /**
     * CreateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper
    )
    {
        parent::__construct($renderer, $urlHelper);
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


        return new HtmlResponse($this->renderer->render('partials::cookies', [
            'form' => $form
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $form = new CookieConsent($this->getCsrfGuard($request));
        $cookies = $request->getCookieParams();

        $data = $request->getParsedBody();
        $form->setData($data);

        // it's assumed that you'll be going to the start after setting cookies settings
        $test = $request->getQueryParams();
        $response = new RedirectResponse($this->urlHelper->generate('home'));

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
