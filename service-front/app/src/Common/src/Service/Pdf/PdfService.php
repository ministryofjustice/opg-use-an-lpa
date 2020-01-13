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
     * @var string
     */
    private $apiBaseUri;

    public function __construct(TemplateRendererInterface $renderer, ClientInterface $httpClient, string $apiBaseUri)
    {
        $this->renderer = $renderer;
        $this->httpClient = $httpClient;
        $this->apiBaseUri = $apiBaseUri;
    }

    public function getLpaAsPdf(Lpa $lpa): StreamInterface
    {
        $url = new Uri($this->apiBaseUri . '/generate-pdf');

        $request = new Request('POST', $url, [], $this->renderer->render('viewer::view-lpa', [
            'lpa' => $lpa,
        ]));

        try {
            $response = $this->httpClient->sendRequest($request);

            switch ($response->getStatusCode()) {
                case StatusCodeInterface::STATUS_OK:
                case StatusCodeInterface::STATUS_CREATED:
                case StatusCodeInterface::STATUS_ACCEPTED:
                case StatusCodeInterface::STATUS_NO_CONTENT:
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