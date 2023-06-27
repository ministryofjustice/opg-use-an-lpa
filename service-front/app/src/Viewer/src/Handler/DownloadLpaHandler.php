<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\Traits\Session as SessionTrait;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\LpaService;
use Common\Service\Pdf\PdfResponse;
use Common\Service\Pdf\PdfService;
use DateTimeImmutable;
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

    public function __construct(
        private LpaService $lpaService,
        private PdfService $pdfService,
        private FeatureEnabled $featureEnabled,
        private LoggerInterface $logger,
    ) {}

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

        $lpa = $this->lpaService->getLpaByCode($code, $surname, null);
        $image = null;

        if (($this->featureEnabled)('instructions_and_preferences') && $lpa->offsetExists('iap')) {
            // TODO UML-2930 This date logic needs removing 30 days after 4th July (or whenever we go live, whichever
            //      is later)
            // necessary for development. Do not uncomment for live environments.
            $lpa->expires = (new DateTimeImmutable('+60 days'))->format(\DateTimeInterface::ATOM);

            $codeCreated = (new DateTimeImmutable($lpa->expires))->sub(new \DateInterval('P30D'));

            if ($codeCreated > new DateTimeImmutable('2023-07-04T23:59:59+01:00')) {
                $images = $lpa->iap; // TODO UML-2930 this is the only bit that should be kept
            }
        }

        $pdfStream = $this->pdfService->getLpaAsPdf($lpa->lpa, $images);

        return new PdfResponse($pdfStream, 'lpa-' . $lpa->lpa->getUId());
    }
}
