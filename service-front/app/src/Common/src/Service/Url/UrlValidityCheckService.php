<?php

declare(strict_types=1);

namespace Common\Service\Url;

use Laminas\Diactoros\ServerRequestFactory;
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

    public function __construct(ServerRequestFactory $serverRequestFactory, RouterInterface $router)
    {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->router = $router;
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
}
