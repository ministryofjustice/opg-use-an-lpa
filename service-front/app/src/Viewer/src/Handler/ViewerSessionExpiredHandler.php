<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ViewerSessionExpiredHandler
 * @package Viewer\Handler
 * @codeCoverageIgnore
 */
class ViewerSessionExpiredHandler extends AbstractHandler
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render('viewer::viewer-session-expired'));
    }
}
