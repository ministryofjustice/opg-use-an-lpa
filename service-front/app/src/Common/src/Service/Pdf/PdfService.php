<?php

declare(strict_types=1);

namespace Common\Service\Pdf;

use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\Log\EventCodes;
use Common\Service\Log\RequestTracing;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Client\Exception\HttpException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Log\LoggerInterface;

class PdfService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $apiBaseUri;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    /**
     * @var StylesService
     */
    private $styles;

    /**
     * @var string
     */
    private $traceId;

    public function __construct(
        TemplateRendererInterface $renderer,
        ClientInterface $httpClient,
        StylesService $styles,
        LoggerInterface $logger,
        string $apiBaseUri,
        string $traceId
    ) {
        $this->renderer = $renderer;
        $this->httpClient = $httpClient;
        $this->styles = $styles;
        $this->logger = $logger;
        $this->apiBaseUri = $apiBaseUri;
        $this->traceId = $traceId;
    }

    /**
     * @param Lpa $lpa
     * @return StreamInterface
     */
    public function getLpaAsPdf(Lpa $lpa): StreamInterface
    {
        $renderedLpa = $this->renderLpaAsHtml($lpa);

        return $this->requestPdfFromService($renderedLpa);
    }

    /**
     * @param Lpa $lpa
     * @return string
     */
    private function renderLpaAsHtml(Lpa $lpa): string
    {
        return $this->renderer->render(
            'viewer::download-lpa',
            [
                'lpa' => $lpa,
                'pdfStyles' => ($this->styles)(),
            ]
        );
    }

    /**
     * @param string $htmlToRender
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
                            'code' => $response->getStatusCode()
                        ]
                    );

                    return $response->getBody();
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            $response = ($ex instanceof HttpException) ? $ex->getResponse() : null;

            $this->logger->error(
                'Failed to generate PDF from service {code} {response}',
                [
                    'code'      => $ex->getCode(),
                    'response'  => $response
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
            'strip-anchor-tags'  => true,
            'print-background'   => true,
            'emulate-media-type' => 'screen',
        ];

        if (!empty($this->traceId)) {
            $headers[RequestTracing::TRACE_HEADER_NAME] = $this->traceId;
        }

        return $headers;
    }
}
