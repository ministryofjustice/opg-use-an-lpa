<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\SessionAware;
use Common\Handler\Traits\Session;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Features\FeatureEnabled;
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
class DownloadLpaHandler implements RequestHandlerInterface, SessionAware
{
    use Session;

    public function __construct(
        private LoggerInterface $logger,
        private FeatureEnabled $featureEnabled,
        private LpaService $lpaService,
        private PdfService $pdfService,
    ) {
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

        $lpa    = $this->lpaService->getLpaByCode($code, $surname, null);
        $images = null;

        if ($lpa->offsetExists('iap')) {
            $images = $lpa->iap;
        }

        $pdfStream = $this->pdfService->getLpaAsPdf($lpa->lpa, $images);

        return new PdfResponse($pdfStream, 'lpa-' . $lpa->lpa->getUId());
    }
}
