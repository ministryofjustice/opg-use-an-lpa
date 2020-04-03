<?php

declare(strict_types=1);

namespace Viewer\Handler;

use ArrayObject;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Log\LoggerInterface;
use Common\Handler\Traits\Logger;

/**
 * Class CheckCodeHandler
 * @package Viewer\Handler
 */
class CheckCodeHandler extends AbstractHandler
{
    use SessionTrait;
    use Logger;

    /** @var LpaService */
    private $lpaService;

    /**
     * EnterCodeHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param LpaService $lpaService
     * @param LoggerInterface $logger
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LpaService $lpaService,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
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

                    if (isset($lpa->cancelled)) {
                        $this->getLogger()->info('The share code used to view LPA {uId} is cancelled',
                            ['uId' => ($lpa->lpa)->getUId()]);

                        return new HtmlResponse($this->renderer->render('viewer::check-code-cancelled'));
                    } else {
                        return new HtmlResponse($this->renderer->render(
                            'viewer::check-code-found',
                            [
                                'lpa' => $lpa->lpa,
                                'expires' => $expires->format('Y-m-d')
                            ]
                        ));
                    }
                }
            } catch (ApiException $apiEx) {
                if ($apiEx->getCode() == StatusCodeInterface::STATUS_GONE) {
                    $this->getLogger()->info('The share code used to view LPA is expired');
                    return new HtmlResponse($this->renderer->render('viewer::check-code-expired'));
                }
            }

            $this->getLogger()->info('The code used to view LPA is not found');
            return new HtmlResponse($this->renderer->render('viewer::check-code-not-found'));
        }

        //  We don't have a code so the session has timed out
        throw new SessionTimeoutException();
    }
}
