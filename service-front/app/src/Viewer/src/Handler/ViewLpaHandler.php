<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Service\Lpa\LpaService;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class ViewLpaHandler
 * @package Viewer\Handler
 */
class ViewLpaHandler extends AbstractHandler
{
    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * ViewLpaHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param LpaService $lpaService
     */
    public function __construct(TemplateRendererInterface $renderer, UrlHelper $urlHelper, LpaService $lpaService)
    {
        parent::__construct($renderer, $urlHelper);

        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $lpaId = $request->getAttribute('id');

        $lpa = $this->lpaService->getLpaById((int) $lpaId);

        //  TODO - Check that the user is allowed to view this LPA? Re-verify the share code?

        return new HtmlResponse($this->renderer->render('app::view-lpa', [
            'lpa' => $lpa,
        ]));
    }
}
