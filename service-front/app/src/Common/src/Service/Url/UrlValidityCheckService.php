<?php

declare(strict_types=1);

namespace Common\Service\Url;

use Laminas\Diactoros\ServerRequestFactory;
use Locale;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouterInterface;

class UrlValidityCheckService
{
    private string $locale;

    public function __construct(
        private ServerRequestFactory $serverRequestFactory,
        private RouterInterface $router,
        private UrlHelper $urlHelper,
    ) {
        $this->locale = Locale::getDefault();
    }

    public function isValid(string $referrerUrl): bool
    {
        // Remove all illegal characters from a URL
        $url = filter_var($referrerUrl, FILTER_SANITIZE_URL);

        // Validate url
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function checkReferrerRouteValid(string $referrerUrl): bool
    {
        if ($this->locale === 'cy_GB') {
            $referrerUrl = str_replace('/cy', '', $referrerUrl);
        }

        $request = $this->serverRequestFactory->createServerRequest('GET', $referrerUrl);
        $result  = $this->router->match($request);

        return $result->isSuccess();
    }

    public function setValidReferrer(?string $referrer): string
    {
        if (!empty($referrer)) {
            $validUrl = $this->isValid($referrer);

            $isValidRefererRoute = $this->checkReferrerRouteValid($referrer);

            return $validUrl && $isValidRefererRoute
                ? $referrer
                : $this->generateHomeUrlForCurrentLocale();
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
