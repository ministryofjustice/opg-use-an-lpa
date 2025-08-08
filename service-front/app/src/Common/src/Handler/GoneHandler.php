<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Handler\AbstractHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 * @psalm-suppress UnusedClass
 */
class GoneHandler extends AbstractHandler
{
    public const TEMPLATE_NAME = 'error::410';
    public const STATUS_CODE   = 410;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    /**
     * Creates and returns a 410 response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render(self::TEMPLATE_NAME), self::STATUS_CODE);
    }
}
