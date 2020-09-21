<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ViewerAccessibilityStatementHandler
 * @package Viewer\Handler
 * @codeCoverageIgnore
 */
class ViewerAccessibilityStatementHandler extends AbstractHandler
{
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render('viewer::viewer-accessibility-statement'));
    }
}
