<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Catches a session timeout exception and displays the relevant error page.
 */
class SessionTimeoutMiddleware implements MiddlewareInterface
{
    public function __construct(protected TemplateRendererInterface $renderer)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (SessionTimeoutException) {
            return new HtmlResponse($this->renderer->render('error::session-timeout'));
        }
    }
}
