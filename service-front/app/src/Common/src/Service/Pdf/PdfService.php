<?php

declare(strict_types=1);

namespace Common\Service\Pdf;

use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Client\Exception\HttpException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class PdfService
{
    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var StylesService
     */
    private $styles;

    /**
     * @var string
     */
    private $apiBaseUri;

    public function __construct(
        TemplateRendererInterface $renderer,
        ClientInterface $httpClient,
        StylesService $styles,
        string $apiBaseUri
    ) {
        $this->renderer = $renderer;
        $this->httpClient = $httpClient;
        $this->styles = $styles;
        $this->apiBaseUri = $apiBaseUri;
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
            'POST', $url, [
            'Content-Type' => 'text/html',
            'Strip-Anchor-Tags' => true,
        ], $htmlToRender
        );

        try {
            $response = $this->httpClient->sendRequest($request);

            switch ($response->getStatusCode()) {
                case StatusCodeInterface::STATUS_OK:
                    return $response->getBody();
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            $response = ($ex instanceof HttpException) ? $ex->getResponse() : null;

            throw ApiException::create('Error whilst making http POST request', $response, $ex);
        }
    }
}