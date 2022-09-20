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
 * @codeCoverageIgnore
 */
class DownloadLpaHandler implements RequestHandlerInterface
{
    use SessionTrait;

    /**
     * @var LoggerInterface;
     */
    private LoggerInterface $logger;

    public function __construct(
        private LpaService $lpaService,
        private PdfService $pdfService,
        LoggerInterface $logger,
    ) {
        $this->logger = $logger;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $code    = $this->getSession($request, 'session')->get('code');
        $surname = $this->getSession($request, 'session')->get('surname');

        if (!isset($code)) {
            $this->logger->error('Session timed out unable to generated PDF');

            throw new SessionTimeoutException();
        }

        $lpa       = $this->lpaService->getLpaByCode($code, $surname, null);
        $pdfStream = $this->pdfService->getLpaAsPdf($lpa->lpa);

        return new PdfResponse($pdfStream, 'lpa-' . $lpa->lpa->getUId());
    }
}
