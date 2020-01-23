<?php

declare(strict_types=1);

namespace CommonTest\Service\Pdf;

use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\Pdf\PdfService;
use Common\Service\Pdf\StylesService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class PdfServiceTest extends TestCase
{
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

    /** @test */
    public function it_returns_a_stream_when_given_an_lpa()
    {
        $lpa = new Lpa();

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render(
            'viewer::download-lpa',
            [
                'lpa' => $lpa,
                'pdfStyles' => '',
            ]
        )->willReturn('<html></html>');

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('', 200));

        $stylesProphecy = $this->prophesize(StylesService::class);
        $stylesProphecy->__invoke()->willReturn('');

        $service = new PdfService(
            $rendererProphecy->reveal(),
            $clientProphecy->reveal(),
            $stylesProphecy->reveal(),
            'http://pdf-service:8080'
        );

        $pdfStream = $service->getLpaAsPdf($lpa);

        $this->assertInstanceOf(StreamInterface::class, $pdfStream);
    }

    /** @test */
    public function it_throws_an_exception_when_response_not_ok()
    {
        $lpa = new Lpa();

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render(
            'viewer::download-lpa',
            [
                'lpa' => $lpa,
                'pdfStyles' => '',
            ]
        )->willReturn('<html></html>');

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('', 500));

        $stylesProphecy = $this->prophesize(StylesService::class);
        $stylesProphecy->__invoke()->willReturn('');

        $service = new PdfService(
            $rendererProphecy->reveal(),
            $clientProphecy->reveal(),
            $stylesProphecy->reveal(),
            'http://pdf-service:8080'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(500);
        $pdfStream = $service->getLpaAsPdf($lpa);
    }

    /** @test */
    public function it_handles_a_client_exception_by_throwing_an_api_exception()
    {
        $lpa = new Lpa();

        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render(
            'viewer::download-lpa',
            [
                'lpa' => $lpa,
                'pdfStyles' => '',
            ]
        )->willReturn('<html></html>');

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->sendRequest(Argument::type(RequestInterface::class))
            ->willThrow($this->prophesize(ClientExceptionInterface::class)->reveal());

        $stylesProphecy = $this->prophesize(StylesService::class);
        $stylesProphecy->__invoke()->willReturn('');

        $service = new PdfService(
            $rendererProphecy->reveal(),
            $clientProphecy->reveal(),
            $stylesProphecy->reveal(),
            'http://pdf-service:8080'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(500);
        $pdfStream = $service->getLpaAsPdf($lpa);
    }
}