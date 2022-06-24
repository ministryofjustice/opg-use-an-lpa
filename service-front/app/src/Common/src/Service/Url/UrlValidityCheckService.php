<?php

declare(strict_types=1);

namespace Common\Service\Url;

use Laminas\Diactoros\ServerRequestFactory;
use Locale;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouterInterface;

/**
 * Class UrlValidityCheckService
 * @package Common\Service\Url
 */
class UrlValidityCheckService
{
    private ServerRequestFactory $serverRequestFactory;
    private RouterInterface $router;
    private UrlHelper $urlHelper;
    private string $locale;

    public function __construct(
        ServerRequestFactory $serverRequestFactory,
        RouterInterface $router,
        UrlHelper $urlHelper
    ) {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->router               = $router;
        $this->urlHelper            = $urlHelper;
        $this->locale               = Locale::getDefault();
    }

    public function isValid(string $referrerUrl): bool
    {
        // Remove all illegal characters from a URL
        $url = filter_var($referrerUrl, FILTER_SANITIZE_URL);

        // Validate url
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function checkRefererRouteValid(string $refererUrl): bool
    {
        if ($this->locale === 'cy_GB') {
            $refererUrl = str_replace('/cy', '', $refererUrl);
        }

        $request = $this->serverRequestFactory->createServerRequest('GET', $refererUrl);
        $result = $this->router->match($request);

        return $result->isSuccess();
    }

    public function setValidReferer(?string $referer): string
    {
        if (!empty($referer)) {
            $validUrl = $this->isValid($referer);

            $isValidRefererRoute = $this->checkRefererRouteValid($referer);

            return ($validUrl && $isValidRefererRoute ? $referer : $this->generateHomeUrlForCurrentLocale());
        }

        return $this->generateHomeUrlForCurrentLocale();
    }

    public function generateHomeUrlForCurrentLocale(): string
    {
        if ($this->locale === 'cy_GB') {
            $homeUrl = $this->urlHelper->generate('home');
            return str_replace('/home', '/cy/home', $homeUrl);
        } else {
            return $this->urlHelper->generate('home');
        }
    }
}
