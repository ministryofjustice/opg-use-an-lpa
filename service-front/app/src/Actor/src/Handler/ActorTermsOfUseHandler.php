<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Service\Url\UrlValidityCheckService;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class ActorTermsOfUseHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class ActorTermsOfUseHandler extends AbstractHandler
{
    /**
     * @var UrlValidityCheckService
     */
    private $urlValidityCheckService;

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
        return new HtmlResponse($this->renderer->render('actor::actor-terms-of-use'));
    }
}
