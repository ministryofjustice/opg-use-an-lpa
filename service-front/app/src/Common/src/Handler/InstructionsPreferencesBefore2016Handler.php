<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Handler\Traits\User;
use Common\Service\Url\UrlValidityCheckService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class InstructionsPreferencesHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class InstructionsPreferencesBefore2016Handler extends AbstractHandler
{
    use User;

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
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $referer = $this->urlValidityCheckService->setValidReferer($request->getHeaders()['referer'][0]);

        return new HtmlResponse($this->renderer->render('common::instructions-preferences-signed-before-2016', [
            'referer' => $referer,
        ]));
    }
}
