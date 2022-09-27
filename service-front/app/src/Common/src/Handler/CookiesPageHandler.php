<?php

declare(strict_types=1);

namespace Common\Handler;

use Acpr\I18n\TranslatorInterface;
use Common\Form\CookieConsent;
use Common\Handler\Traits\CsrfGuard;
use Common\Service\Url\UrlValidityCheckService;
use DateTime;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * @codeCoverageIgnore
 */
class CookiesPageHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;

    private const COOKIE_POLICY_NAME   = 'cookie_policy';
    private const SEEN_COOKIE_NAME     = 'seen_cookie_message';
    public const COOKIES_SET_FLASH_MSG = 'cookies_set_flash_msg';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private UrlValidityCheckService $urlValidityCheckService,
        private TranslatorInterface $translator,
        private string $application = 'actor',
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var FlashMessagesInterface $flash */
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $form = new CookieConsent($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        $cookies = $request->getCookieParams();

        $usageCookies = 'no';
        if (array_key_exists(self::COOKIE_POLICY_NAME, $cookies)) {
            $cookiePolicy = json_decode($cookies[self::COOKIE_POLICY_NAME], true);
            $usageCookies = $cookiePolicy['usage'] === true ? 'yes' : 'no';
        }
        $form->get('usageCookies')->setValue($usageCookies);

        //if flash message is displayed we have been redirected. We need to change to the previous referer
        if ($flash->getFlash($this::COOKIES_SET_FLASH_MSG) !== null) {
            $cookiesPageReferer = $flash->getFlash($this::COOKIES_SET_FLASH_MSG);
        } else {
            $cookiesPageReferer = $request->getHeaders()['referer'][0] ?? null;
        }

        $form->get('referer')->setValue($this->urlValidityCheckService->setValidReferrer($cookiesPageReferer));

        return new HtmlResponse($this->renderer->render('common::cookies', [
            'form'        => $form,
            'application' => $this->application,
            'flash'       => $flash,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $form = new CookieConsent($this->getCsrfGuard($request));
        $form->setData($request->getParsedBody());

        $response = new RedirectResponse($this->urlHelper->generate('cookies'));

        /** @var FlashMessagesInterface $flash */
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
        $flash->flash(self::COOKIES_SET_FLASH_MSG, $form->get('referer')->getValue());

        $cookiePolicy              = [];
        $cookiePolicy['essential'] = true;
        $cookiePolicy['usage']     = $form->get('usageCookies')->getValue() === 'yes';

        return FigResponseCookies::set(
            $response,
            SetCookie::create(self::COOKIE_POLICY_NAME, json_encode($cookiePolicy))
                ->withHttpOnly(false)
                ->withExpires(new DateTime('+365 days'))
                ->withPath('/')
        );
    }
}
