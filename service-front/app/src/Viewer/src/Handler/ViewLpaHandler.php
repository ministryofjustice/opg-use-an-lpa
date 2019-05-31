<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Middleware\Session\SessionTimeoutException;
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
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $code = $this->getSession($request,'session')->get('code');

        if (!isset($code)) {
            throw new SessionTimeoutException;
        }

        $lpa = $this->lpaService->getLpaByCode($code);

        return new HtmlResponse($this->renderer->render('viewer::view-lpa', [
            'lpa' => $lpa,
        ]));
    }
}
