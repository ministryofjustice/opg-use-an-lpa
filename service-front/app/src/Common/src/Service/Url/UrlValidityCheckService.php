<?php

declare(strict_types=1);

namespace Common\Service\Url;

use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouterInterface;

/**
 * Class UrlValidityCheckService
 * @package Common\Service\Url
 */
class UrlValidityCheckService
{
    /**
     * @var ServerRequestFactory
     */
    private $serverRequestFactory;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var UrlHelper
     */
    private UrlHelper $urlHelper;

    public function __construct(
        ServerRequestFactory $serverRequestFactory,
        RouterInterface $router,
        UrlHelper $urlHelper
    ) {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->router               = $router;
        $this->urlHelper            = $urlHelper;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isValid(string $refererUrl): bool
    {
        // Remove all illegal characters from a url
        $url = filter_var($refererUrl, FILTER_SANITIZE_URL);

        // Validate url
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function checkRefererRouteValid(string $refererUrl): bool
    {
        if ($refererUrl !== null) {
            $request = $this->serverRequestFactory->createServerRequest('GET', $refererUrl);
            $result = $this->router->match($request);

            if ($result) {
                return true;
            }
        }
        return false;
    }

    public function setValidReferer(string $referer): string
    {
        if (!empty($referer)) {
            $validUrl = $this->isValid($referer);

            $isValidRefererRoute = $this->checkRefererRouteValid($referer);

            return ($validUrl && $isValidRefererRoute ? $referer : $this->urlHelper->generate('home'));
        }

        return $this->urlHelper->generate('home');
    }
}
