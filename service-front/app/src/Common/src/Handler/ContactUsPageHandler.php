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
 * @codeCoverageIgnore
 */
class ContactUsPageHandler extends AbstractHandler
{
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private UrlValidityCheckService $urlValidityCheckService,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $refererHeader = $request->getHeaders()['referer'][0] ?? null;

        $referer = $this->urlValidityCheckService->setValidReferrer($refererHeader);
        return new HtmlResponse($this->renderer->render('common::contact-us', [
            'referer' => $referer,
        ]));
    }
}
