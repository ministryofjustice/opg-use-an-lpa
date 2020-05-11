<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CookieConsent;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;

/**
 * Class CookiesPageHandler
 * @package Actor\Handler
 */
class CookiesPageHandler extends AbstractHandler
{
    const COOKIE_POLICY_NAME = 'cookie_policy';
    const SEEN_COOKIE_NAME   = 'seen_cookie_message';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() == 'POST') {
            return $this->handlePost($request);
        }

        $form = new CookieConsent();
        $cookies = $request->getCookieParams();

        $usageCookies = 'no';
        if (array_key_exists(self::COOKIE_POLICY_NAME, $cookies)) {
            $cookiePolicy = json_decode($cookies[self::COOKIE_POLICY_NAME], true);
            $usageCookies = $cookiePolicy['usage'] === true ? 'yes' : 'no';
        }
        $form->get('usage-cookies')->setValue($usageCookies);

        $response =  new HtmlResponse($this->getTemplateRenderer()->render('app::cookies-page', [
            'form' => $form
        ]));

        return $response;
    }

    public function handlePost(ServerRequestInterface $request) : ResponseInterface
    {
        $form = new CookieConsent();
        $cookies = $request->getCookieParams();

        $data = $request->getParsedBody();
        $form->setData($data);

        // it's assumed that you'll be going to the start after setting cookies settings
        $response = new RedirectResponse($this->getUrlHelper()->generate('start'));

        if (array_key_exists(self::COOKIE_POLICY_NAME, $cookies)) {

            try {
                $cookiePolicy = json_decode($cookies[self::COOKIE_POLICY_NAME], true);
            } catch (\Exception $e) {
                return $response;
            }

            $cookiePolicy['usage'] = $form->get('usage-cookies')->getValue() === 'yes' ? true : false;

            $response = FigResponseCookies::set($response,
                SetCookie::create(self::COOKIE_POLICY_NAME, json_encode($cookiePolicy))
                    ->withHttpOnly(false)
                    ->withExpires(new \DateTime('+365 days'))
                    ->withSecure(true)
                    ->withPath('/')
            );

            $response = FigResponseCookies::set($response,
                SetCookie::create(self::SEEN_COOKIE_NAME, "true")
                    ->withHttpOnly(false)
                    ->withExpires(new \DateTime('+30 days'))
                    ->withSecure(true)
                    ->withPath('/')
            );
        }

        return $response;
    }
}
