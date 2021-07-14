<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Service\Url\UrlValidityCheckService;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class ContactUsPageHandler
 * @package Common\Handler
 * @codeCoverageIgnore
 */
class ContactUsPageHandler extends AbstractHandler
{
    /**
     * @var UrlValidityCheckService
     */
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
        $referer = $this->urlValidityCheckService->setValidReferer($request->getHeaders()['referer'][0]);
        return new HtmlResponse($this->renderer->render('partials::contact-us', [
            'referer' => $referer
        ]));
    }
}
