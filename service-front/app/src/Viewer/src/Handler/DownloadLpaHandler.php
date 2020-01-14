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

/**
 * Class DownloadLpaHandler
 * @package Viewer\Handler
 */
class DownloadLpaHandler implements RequestHandlerInterface
{
    use SessionTrait;

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
     * @param LpaService $lpaService
     * @param PdfService $pdfService
     */
    public function __construct(
        LpaService $lpaService,
        PdfService $pdfService)
    {
        $this->lpaService = $lpaService;
        $this->pdfService = $pdfService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $code = $this->getSession($request,'session')->get('code');
        $surname = $this->getSession($request,'session')->get('surname');

        if (!isset($code)) {
            throw new SessionTimeoutException;
        }

        $lpa = $this->lpaService->getLpaByCode($code, $surname, LpaService::FULL);
        $pdfStream = $this->pdfService->getLpaAsPdf($lpa->lpa);

        return new PdfResponse($pdfStream, 'lpa-' . $lpa->lpa->getUId());
    }
}
