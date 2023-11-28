<?php

declare(strict_types=1);

namespace Common\Handler;

use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractHandler implements RequestHandlerInterface
{
    public function __construct(
        protected TemplateRendererInterface $renderer,
        protected UrlHelper $urlHelper,
        protected ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function handle(ServerRequestInterface $request): ResponseInterface;

    protected function redirectToRoute($route, $routeParams = [], $queryParams = [], ?string $basePath = null): RedirectResponse
    {
        if ($basePath !== null) {
            $this->urlHelper->setBasePath($basePath);
        }

        return new RedirectResponse($this->urlHelper->generate($route, $routeParams, $queryParams));
    }
}
