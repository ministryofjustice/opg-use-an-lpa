<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ViewerPrivacyNoticeHandler
 * @package Viewer\Handler
 * @codeCoverageIgnore
 */
class ViewerPrivacyNoticeHandler extends AbstractHandler
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render('viewer::viewer-privacy-notice'));
    }
}
