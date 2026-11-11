<?php

declare(strict_types=1);

namespace CommonTest\Service\Pdf;

use Common\Entity\CombinedLpa;
use Common\Entity\InstructionsAndPreferences\Images;
use Common\Entity\InstructionsAndPreferences\ImagesStatus;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\Log\EventCodes;
use Common\Service\Pdf\PdfService;
use Common\Service\Pdf\StylesService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(PdfService::class)]
#[UsesClass(ApiException::class)]
final class PdfServiceTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private ClientInterface&MockObject $client;
    private LoggerInterface&MockObject $logger;
    private PdfService $service;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->client   = $this->createMock(ClientInterface::class);
        $this->logger   = $this->createMock(LoggerInterface::class);
        $styles         = $this->createStub(StylesService::class);
        $styles->method('__invoke')->willReturn('pdf styles');

        $this->service = new PdfService(
            $this->renderer,
            $this->client,
            $styles,
            $this->logger,
            'http://pdf-service:8080',
        );
    }

    #[Test]
    public function it_returns_a_stream_when_given_an_lpa_without_images(): void
    {
        $lpa = new Lpa();
        $this->renderer->expects(self::once())->method('render')->with(
            'viewer::download-lpa',
            [
                'lpa'       => $lpa,
                'pdfStyles' => 'pdf styles',
            ],
        )->willReturn('<html></html>');
        $response = new Response(200, [], 'PDF content');
        $this->client->expects(self::once())->method('sendRequest')->with(self::callback(
            static function (RequestInterface $request): bool {
                self::assertSame('POST', $request->getMethod());
                self::assertSame('http://pdf-service:8080/generate-pdf', (string) $request->getUri());
                self::assertSame('<html></html>', (string) $request->getBody());
                self::assertSame([
                    'Host'               => ['pdf-service:8080'],
                    'Content-Type'       => ['text/html'],
                    'Strip-Anchor-Tags'  => ['true'],
                    'Print-Background'   => ['true'],
                    'Emulate-Media-Type' => ['screen'],
                ], $request->getHeaders());

                return true;
            },
        ))->willReturn($response);
        $this->logger->expects(self::once())->method('notice')->with(
            'Successfully generated PDF and presented for download {code}',
            [
                'event_code' => EventCodes::DOWNLOAD_SUMMARY,
                'code'       => 200,
            ],
        );

        self::assertSame($response->getBody(), $this->service->getLpaAsPdf($lpa));
    }

    #[Test]
    public function it_returns_a_stream_when_given_an_lpa_with_images(): void
    {
        $lpa    = new Lpa();
        $images = new Images(700000000001, ImagesStatus::COLLECTION_COMPLETE, []);
        $this->renderer->expects(self::once())->method('render')->with(
            'viewer::download-lpa',
            [
                'lpa'        => $lpa,
                'iap_images' => $images,
                'pdfStyles'  => 'pdf styles',
            ],
        )->willReturn('<html></html>');
        $response = new Response(200, [], 'PDF content');
        $this->client->expects(self::once())->method('sendRequest')->willReturn($response);
        $this->logger->expects(self::once())->method('notice');

        self::assertSame($response->getBody(), $this->service->getLpaAsPdf($lpa, $images));
    }

    #[Test]
    public function it_renders_datastore_lpas_with_the_combined_template(): void
    {
        $lpa = $this->createStub(CombinedLpa::class);
        $this->renderer->expects(self::once())->method('render')->with(
            'viewer::download-lpa-combined-lpa',
            [
                'lpa'       => $lpa,
                'pdfStyles' => 'pdf styles',
            ],
        )->willReturn('<html></html>');
        $response = new Response(200);
        $this->client->expects(self::once())->method('sendRequest')->willReturn($response);
        $this->logger->expects(self::once())->method('notice');

        self::assertSame($response->getBody(), $this->service->getLpaAsPdf($lpa));
    }

    #[Test]
    public function it_throws_an_exception_when_response_not_ok(): void
    {
        $this->renderer->expects(self::once())->method('render')->willReturn('<html></html>');
        $this->client->expects(self::once())->method('sendRequest')->willReturn(new Response(500));
        $this->logger->expects(self::never())->method('notice');
        $this->logger->expects(self::never())->method('error');

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(500);
        $this->service->getLpaAsPdf(new Lpa());
    }

    #[Test]
    public function it_handles_a_client_exception_by_throwing_an_api_exception(): void
    {
        $this->renderer->expects(self::once())->method('render')->willReturn('<html></html>');
        $exception = new ConnectException('Connection failed', new Request('POST', 'http://pdf-service:8080'));
        $this->client->expects(self::once())->method('sendRequest')->willThrowException($exception);
        $this->logger->expects(self::once())->method('error')->with(
            'Failed to generate PDF from service {code} {response}',
            [
                'code'     => 0,
                'response' => null,
            ],
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Error whilst making http POST request to PDF Service');
        $this->service->getLpaAsPdf(new Lpa());
    }

    #[Test]
    public function it_preserves_the_response_from_an_http_client_exception(): void
    {
        $this->renderer->expects(self::once())->method('render')->willReturn('<html></html>');
        $response  = new Response(503);
        $exception = new ServerException('Service unavailable', new Request('POST', 'http://pdf-service'), $response);
        $this->client->expects(self::once())->method('sendRequest')->willThrowException($exception);
        $this->logger->expects(self::once())->method('error')->with(
            'Failed to generate PDF from service {code} {response}',
            [
                'code'     => 503,
                'response' => $response,
            ],
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(503);
        $this->service->getLpaAsPdf(new Lpa());
    }
}
