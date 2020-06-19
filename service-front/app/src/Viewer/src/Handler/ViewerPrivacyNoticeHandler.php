<?php


namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

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
