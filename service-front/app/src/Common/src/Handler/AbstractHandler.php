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

/**
 * @codeCoverageIgnore
 */
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

    /**
     * Handles a redirect to route
     * localeOverride used to override from English to Welsh only
     *
     * @param $route
     * @param $routeParams
     * @param $queryParams
     * @param string|null $localeOverride
     * @return RedirectResponse
     */
    public function redirectToRoute($route, $routeParams = [], $queryParams = [], ?string $localeOverride = null): RedirectResponse
    {
        //TODO: UML-3203 Identify if OneLogin can handle multiple redirect urls, then remove
        if ($localeOverride !== null) {
            $this->urlHelper->setBasePath($localeOverride);
        }
        return new RedirectResponse($this->urlHelper->generate($route, $routeParams, $queryParams));
    }
}
