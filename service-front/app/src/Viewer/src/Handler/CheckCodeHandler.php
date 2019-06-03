<?php

declare(strict_types=1);

namespace Viewer\Handler;

use ArrayObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Middleware\Session\SessionTimeoutException;
use Viewer\Service\Lpa\LpaService;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class CheckCodeHandler
 * @package Viewer\Handler
 */
class CheckCodeHandler extends AbstractHandler
{
    /** @var LpaService */
    private $lpaService;

    /**
     * EnterCodeHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param LpaService $lpaService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LpaService $lpaService)
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

        } else {

            $lpa = $this->lpaService->getLpaByCode($code);

            if ($lpa instanceof ArrayObject) {

                // Then we found a LPA for the given code
                return new HtmlResponse($this->renderer->render('viewer::check-code-found', [
                    'lpa' => $lpa,
                ]));
            }
        }

        // If we get here then we couldn't find an LPA for the given code.
        return new HtmlResponse($this->renderer->render('viewer::check-code-not-found'));
    }
}
