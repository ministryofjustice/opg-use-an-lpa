<?php

declare(strict_types=1);

namespace Common\Service\Pdf;

use Common\Entity\InstructionsAndPreferences\Images;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\Log\EventCodes;
use Common\Service\Log\RequestTracing;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Client\Exception\HttpException;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class PdfService
{
    public function __construct(
        private TemplateRendererInterface $renderer,
        private ClientInterface $httpClient,
        private StylesService $styles,
        private LoggerInterface $logger,
        private string $apiBaseUri,
        private string $traceId,
    ) {
    }

    public function getLpaAsPdf(Lpa $lpa, ?Images $images = null): StreamInterface
    {
        $renderedLpa = $this->renderLpaAsHtml($lpa, $images);

        return $this->requestPdfFromService($renderedLpa);
    }

    private function renderLpaAsHtml(Lpa $lpa, ?Images $images): string
    {
        $renderData = [
            'lpa'       => $lpa,
            'pdfStyles' => ($this->styles)(),
        ];

        if ($images !== null) {
            $renderData['iap_images'] = $images;
        }

        return $this->renderer->render(
            'viewer::download-lpa',
            $renderData,
        );
    }

    /**
     * @param  string $htmlToRender
     * @return StreamInterface
     * @throws ApiException
     */
    private function requestPdfFromService(string $htmlToRender): StreamInterface
    {
        $url = new Uri($this->apiBaseUri . '/generate-pdf');

        $request = new Request(
            'POST',
            $url,
            $this->generateHeaders(),
            $htmlToRender
        );

        try {
            $response = $this->httpClient->sendRequest($request);

            switch ($response->getStatusCode()) {
            case StatusCodeInterface::STATUS_OK:
                $this->logger->notice(
                    'Successfully generated PDF and presented for download {code}',
                    [
                        'event_code' => EventCodes::DOWNLOAD_SUMMARY,
                        'code'       => $response->getStatusCode(),
                    ]
                );

                return $response->getBody();
            default:
                throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            $response = $ex instanceof HttpException ? $ex->getResponse() : null;

            $this->logger->error(
                'Failed to generate PDF from service {code} {response}',
                [
                    'code'     => $ex->getCode(),
                    'response' => $response,
                ]
            );

            throw ApiException::create('Error whilst making http POST request to PDF Service', $response, $ex);
        }
    }

    /**
     * @return string
     */
    private function generateHeaders(): array
    {
        $headers = [
            'Content-Type'       => 'text/html',
            'Strip-Anchor-Tags'  => 'true',
            'Print-Background'   => 'true',
            'Emulate-Media-Type' => 'screen',
        ];

        if (!empty($this->traceId)) {
            $headers[RequestTracing::TRACE_HEADER_NAME] = $this->traceId;
        }

        return $headers;
    }
}
