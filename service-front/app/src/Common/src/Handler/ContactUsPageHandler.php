<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Service\Url\UrlValidityCheckService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ContactUsPageHandler
 * @package Common\Handler
 * @codeCoverageIgnore
 */
class ContactUsPageHandler extends AbstractHandler
{
    private UrlValidityCheckService $urlValidityCheckService;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UrlValidityCheckService $urlValidityCheckService
    ) {
        parent::__construct($renderer, $urlHelper);
        $this->urlValidityCheckService = $urlValidityCheckService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $refererHeader = $request->getHeaders()['referer'][0] ?? null;

        $referer = $this->urlValidityCheckService->setValidReferer($refererHeader);
        return new HtmlResponse($this->renderer->render('common::contact-us', [
            'referer' => $referer
        ]));
    }
}
