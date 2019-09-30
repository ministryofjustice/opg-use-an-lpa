<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;
use ArrayObject;
use DateTime;

/**
 * Class CheckCodeHandler
 * @package Viewer\Handler
 */
class CheckCodeHandler extends AbstractHandler
{
    use SessionTrait;

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

        if (isset($code)) {

            try {
                $lpa = $this->lpaService->getLpaByCode($code);

                if ($lpa instanceof ArrayObject) {
                    // Then we found a LPA for the given code
                    $expires = new DateTime($lpa->expires);

                    return new HtmlResponse($this->renderer->render('viewer::check-code-found', [
                        'lpa' => $lpa->lpa,
                        'expires' => $expires->format('Y-m-d'),
                    ]));
                }
            } catch (ApiException $apiEx) {
                if ($apiEx->getCode() == StatusCodeInterface::STATUS_GONE) {
                    return new HtmlResponse($this->renderer->render('viewer::check-code-expired'));
                }
            }

            return new HtmlResponse($this->renderer->render('viewer::check-code-not-found'));
        }

        //  We don't have a code so the session has timed out
        throw new SessionTimeoutException();
    }
}
