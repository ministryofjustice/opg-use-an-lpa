<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;

/**
 * Catches a session timeout exception and displays the relevant error page.
 *
 * Class SessionTimeoutMiddleware
 * @package Common\Middleware\Session
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (SessionTimeoutException $e) {
            return new HtmlResponse($this->renderer->render('error::session-timeout'));
        }
    }
}
