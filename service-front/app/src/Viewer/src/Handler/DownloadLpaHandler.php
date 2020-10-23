<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\Traits\Session as SessionTrait;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use Common\Service\Pdf\PdfResponse;
use Common\Service\Pdf\PdfService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DownloadLpaHandler
 *
 * @package Viewer\Handler
 * @codeCoverageIgnore
 */
class DownloadLpaHandler implements RequestHandlerInterface
{
    use SessionTrait;

    /**
     * @var LoggerInterface;
     */
    private $logger;

    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * @var PdfService
     */
    private $pdfService;

    /**
     * ViewLpaHandler constructor.
     *
     * @param LpaService        $lpaService
     * @param PdfService        $pdfService
     * @param LoggerInterface   $logger
     */
    public function __construct(
        LpaService $lpaService,
        PdfService $pdfService,
        LoggerInterface $logger
    ) {
        $this->lpaService = $lpaService;
        $this->pdfService = $pdfService;
        $this->logger = $logger;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $code = $this->getSession($request, 'session')->get('code');
        $surname = $this->getSession($request, 'session')->get('surname');

        if (!isset($code)) {
            $this->logger->error("Session timed out unable to generated PDF");

            throw new SessionTimeoutException();
        }

        $lpa = $this->lpaService->getLpaByCode($code, $surname, null);
        $pdfStream = $this->pdfService->getLpaAsPdf($lpa->lpa);

        return new PdfResponse($pdfStream, 'lpa-' . $lpa->lpa->getUId());
    }
}
