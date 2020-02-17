<?php

declare(strict_types=1);

namespace Common\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class AbstractHandler
 *
 * @package Common\Handler
 */
abstract class AbstractHandler implements RequestHandlerInterface
{
    /** @var LoggerInterface|null */
    protected $logger;

    /** @var TemplateRendererInterface */
    protected $renderer;

    /** @var UrlHelper */
    protected $urlHelper;

    /**
     * AbstractHandler constructor.
     *
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        ?LoggerInterface $logger = null
    ) {
        $this->renderer = $renderer;
        $this->urlHelper = $urlHelper;
        $this->logger = $logger;
    }

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
     * @return RedirectResponse
     */
    protected function redirectToRoute($route, $routeParams = [], $queryParams = []): RedirectResponse
    {
        return new RedirectResponse(
            $this->urlHelper->generate($route, $routeParams, $queryParams)
        );
    }
}
