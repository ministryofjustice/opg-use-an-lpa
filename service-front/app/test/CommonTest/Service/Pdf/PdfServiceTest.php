<?php

declare(strict_types=1);

namespace CommonTest\Service\Pdf;

use PHPUnit\Framework\Attributes\Test;
use Common\Entity\InstructionsAndPreferences\Images;
use Common\Entity\InstructionsAndPreferences\ImagesStatus;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\Pdf\PdfService;
use Common\Service\Pdf\StylesService;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class PdfServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * Create a {@link ResponseInterface} prophecy that returns a defined response body and code.
     *
     * @param string $body
     * @param int $code
     * @return ObjectProphecy
     */
    protected function setupResponse(string $body, int $code): ObjectProphecy
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->getContents()
            ->willReturn($body);

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()
            ->willReturn($code);
        $responseProphecy->getBody()
            ->willReturn($streamProphecy->reveal());

        return $responseProphecy;
    }

    #[Test]
    public function it_returns_a_stream_when_given_an_lpa_without_images(): void
    {
        $lpa = new Lpa();

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render(
            'viewer::download-lpa',
            [
                'lpa'       => $lpa,
                'pdfStyles' => '',
            ]
        )->willReturn('<html></html>');

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->sendRequest(Argument::that(function (RequestInterface $request) {
            $this->assertCount(1, $request->getHeader('Content-Type'));
            $this->assertEquals('text/html', $request->getHeader('Content-Type')[0]);

            $this->assertCount(1, $request->getHeader('Strip-Anchor-Tags'));
            $this->assertEquals('true', $request->getHeader('Strip-Anchor-Tags')[0]);

            $this->assertCount(1, $request->getHeader('Print-Background'));
            $this->assertEquals('true', $request->getHeader('Print-Background')[0]);

            $this->assertCount(1, $request->getHeader('Emulate-Media-Type'));
            $this->assertEquals('screen', $request->getHeader('Emulate-Media-Type')[0]);

            return true;
        }))
            ->willReturn($this->setupResponse('', 200));

        $stylesProphecy = $this->prophesize(StylesService::class);
        $stylesProphecy->__invoke()->willReturn('');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new PdfService(
            $rendererProphecy->reveal(),
            $clientProphecy->reveal(),
            $stylesProphecy->reveal(),
            $loggerProphecy->reveal(),
            'http://pdf-service:8080',
            'Root=1-1-11'
        );

        $pdfStream = $service->getLpaAsPdf($lpa);

        $this->assertInstanceOf(StreamInterface::class, $pdfStream);
    }

    #[Test]
    public function it_returns_a_stream_when_given_an_lpa_with_images(): void
    {
        $lpa    = new Lpa();
        $images = new Images(700000000001, ImagesStatus::COLLECTION_COMPLETE, []);

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render(
            'viewer::download-lpa',
            [
                'lpa'        => $lpa,
                'iap_images' => $images,
                'pdfStyles'  => '',
            ]
        )->willReturn('<html></html>');

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->sendRequest(Argument::any())
            ->willReturn($this->setupResponse('', 200));

        $stylesProphecy = $this->prophesize(StylesService::class);
        $stylesProphecy->__invoke()->willReturn('');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new PdfService(
            $rendererProphecy->reveal(),
            $clientProphecy->reveal(),
            $stylesProphecy->reveal(),
            $loggerProphecy->reveal(),
            'http://pdf-service:8080',
            'Root=1-1-11'
        );

        $pdfStream = $service->getLpaAsPdf($lpa, $images);

        $this->assertInstanceOf(StreamInterface::class, $pdfStream);
    }

    #[Test]
    public function it_throws_an_exception_when_response_not_ok(): void
    {
        $lpa = new Lpa();

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render(
            'viewer::download-lpa',
            [
                'lpa'       => $lpa,
                'pdfStyles' => '',
            ]
        )->willReturn('<html></html>');

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('', 500));

        $stylesProphecy = $this->prophesize(StylesService::class);
        $stylesProphecy->__invoke()->willReturn('');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new PdfService(
            $rendererProphecy->reveal(),
            $clientProphecy->reveal(),
            $stylesProphecy->reveal(),
            $loggerProphecy->reveal(),
            'http://pdf-service:8080',
            'Root=1-1-11'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(500);
        $pdfStream = $service->getLpaAsPdf($lpa);
    }

    #[Test]
    public function it_handles_a_client_exception_by_throwing_an_api_exception(): void
    {
        $lpa = new Lpa();

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render(
            'viewer::download-lpa',
            [
                'lpa'       => $lpa,
                'pdfStyles' => '',
            ]
        )->willReturn('<html></html>');

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->sendRequest(Argument::type(RequestInterface::class))
            ->willThrow($this->prophesize(ClientExceptionInterface::class)->reveal());

        $stylesProphecy = $this->prophesize(StylesService::class);
        $stylesProphecy->__invoke()->willReturn('');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new PdfService(
            $rendererProphecy->reveal(),
            $clientProphecy->reveal(),
            $stylesProphecy->reveal(),
            $loggerProphecy->reveal(),
            'http://pdf-service:8080',
            'Root=1-1-11'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(500);
        $pdfStream = $service->getLpaAsPdf($lpa);
    }

    #[Test]
    public function it_correctly_attaches_a_tracing_header(): void
    {
        $lpa = new Lpa();

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render(
            'viewer::download-lpa',
            [
                'lpa'       => $lpa,
                'pdfStyles' => '',
            ]
        )->willReturn('<html></html>');

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->sendRequest(Argument::that(function (RequestInterface $request) {
            $this->assertCount(1, $request->getHeader('x-amzn-trace-id'));
            $this->assertEquals('Root=1-1-11', $request->getHeader('x-amzn-trace-id')[0]);

            return true;
        }))
            ->willReturn($this->setupResponse('', 200));

        $stylesProphecy = $this->prophesize(StylesService::class);
        $stylesProphecy->__invoke()->willReturn('');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new PdfService(
            $rendererProphecy->reveal(),
            $clientProphecy->reveal(),
            $stylesProphecy->reveal(),
            $loggerProphecy->reveal(),
            'http://pdf-service:8080',
            'Root=1-1-11'
        );

        $pdfStream = $service->getLpaAsPdf($lpa);

        $this->assertInstanceOf(StreamInterface::class, $pdfStream);
    }
}
