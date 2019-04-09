<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;

abstract class AbstractHandler implements RequestHandlerInterface, Initializer\UrlHelperInterface, Initializer\TemplatingSupportInterface
{
    use Traits\Session;
    use Initializer\TemplatingSupportTrait;
    use Initializer\UrlHelperTrait;

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function handle(ServerRequestInterface $request): ResponseInterface;

    /**
     * Redirect to the specified route
     *
     * @param $route
     * @param array $routeParams
     * @param array $queryParams
     * @return Response\RedirectResponse
     */
    protected function redirectToRoute($route, $routeParams = [], $queryParams = [])
    {
        return new Response\RedirectResponse(
            $this->getUrlHelper()->generate($route, $routeParams, $queryParams)
        );
    }
}
