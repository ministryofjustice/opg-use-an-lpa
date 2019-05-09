<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Zend\Diactoros\Response;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class AbstractHandler
 * @package Viewer\Handler
 */
abstract class AbstractHandler implements RequestHandlerInterface
{
    use Traits\Session;

    /** @var TemplateRendererInterface */
    protected $renderer;

    /** @var UrlHelper */
    protected $urlHelper;

    /**  @var null|FormFactoryInterface */
    protected $formFactory;

    /**
     * AbstractHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     */
    public function __construct(TemplateRendererInterface $renderer, UrlHelper $urlHelper)
    {
        $this->renderer = $renderer;
        $this->urlHelper = $urlHelper;
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
     * @return Response\RedirectResponse
     */
    protected function redirectToRoute($route, $routeParams = [], $queryParams = [])
    {
        return new Response\RedirectResponse(
            $this->urlHelper->generate($route, $routeParams, $queryParams)
        );
    }
}
