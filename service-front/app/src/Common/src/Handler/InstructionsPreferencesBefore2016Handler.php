<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Handler\Traits\User;
use Common\Service\Url\UrlValidityCheckService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class InstructionsPreferencesBefore2016Handler extends AbstractHandler
{
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private UrlValidityCheckService $urlValidityCheckService,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    /**
     * Handles a request and produces a response
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $referer = $this->urlValidityCheckService->setValidReferrer($request->getHeaders()['referer'][0]);

        return new HtmlResponse(
            $this->renderer->render(
                'common::instructions-preferences-signed-before-2016', [
                'referer' => $referer,
                ]
            )
        );
    }
}
