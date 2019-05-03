<?php

declare(strict_types=1);

namespace Viewer\Middleware\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Cached a session timeout exception and display the relevant error page.
 *
 * Class SessionTimeoutMiddleware
 * @package Viewer\Middleware\Session
 */
class SessionTimeoutMiddleware implements MiddlewareInterface
{
    /**
     * @var TemplateRendererInterface
     */
    protected $renderer;

    public function __construct(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (SessionTimeoutException $e){
            return new HtmlResponse($this->renderer->render('error::session-timeout'));
        }
    }
}
