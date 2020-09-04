<?php

declare(strict_types=1);

namespace Viewer\Handler;

use ArrayObject;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use Common\Service\Security\RateLimitService;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class CheckCodeHandler
 *
 * @package Viewer\Handler
 *
 * @codeCoverageIgnore
 */
class CheckCodeHandler extends AbstractHandler
{
    use SessionTrait;

    /** @var LpaService */
    private $lpaService;

    /**
     * @var RateLimitService
     */
    private $failureRateLimiter;

    /**
     * EnterCodeHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param LpaService $lpaService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LpaService $lpaService,
        RateLimitService $failureRateLimiter
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->lpaService = $lpaService;
        $this->failureRateLimiter = $failureRateLimiter;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception|\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $code = $this->getSession($request, 'session')->get('code');
        $surname = $this->getSession($request, 'session')->get('surname');

        if (isset($code)) {
            try {
                $lpa = $this->lpaService->getLpaByCode($code, $surname, LpaService::SUMMARY);

                if ($lpa instanceof ArrayObject) {
                    // Then we found a LPA for the given code
                    $expires = new DateTime($lpa->expires);
                    $status = strtolower(($lpa->lpa)->getStatus());
                    if ($status === 'registered' || $status === 'cancelled') {
                        return new HtmlResponse($this->renderer->render(
                            'viewer::check-code-found',
                            [
                                'lpa' => $lpa->lpa,
                                'expires' => $expires->format('Y-m-d'),
                            ]
                        ));
                    }
                }
            } catch (ApiException $apiEx) {
                if ($apiEx->getCode() == StatusCodeInterface::STATUS_GONE) {
                    if ($apiEx->getMessage() === 'Share code cancelled') {
                        return new HtmlResponse($this->renderer->render('viewer::check-code-cancelled'));
                    } else {
                        return new HtmlResponse($this->renderer->render('viewer::check-code-expired'));
                    }
                }
            }

            $this->failureRateLimiter->limit($request->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE));
            return new HtmlResponse($this->renderer->render('viewer::check-code-not-found'));
        }

        //  We don't have a code so the session has timed out
        throw new SessionTimeoutException();
    }
}
