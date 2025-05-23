<?php

declare(strict_types=1);

namespace CommonTest\Service\Pdf;

use PHPUnit\Framework\Attributes\Test;
use Common\Service\Log\RequestTracing;
use Common\Service\Pdf\PdfService;
use Common\Service\Pdf\PdfServiceFactory;
use Common\Service\Pdf\StylesService;
use DI\Factory\RequestedEntry;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PdfServiceFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_creates_a_configured_pdf_service(): void
    {
        $config = [
            'pdf' => [
                'uri' => 'test',
            ],
        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);

        $templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $containerProphecy->get(TemplateRendererInterface::class)->willReturn($templateRendererProphecy->reveal());

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $containerProphecy->get(ClientInterface::class)->willReturn($clientProphecy->reveal());

        $stylesProphecy = $this->prophesize(StylesService::class);
        $containerProphecy->get(StylesService::class)->willReturn($stylesProphecy->reveal());

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $containerProphecy->get(LoggerInterface::class)->willReturn($loggerProphecy->reveal());

        $containerProphecy->get(RequestTracing::TRACE_PARAMETER_NAME)->willReturn('Root=1-1-11');


        $factory    = new PdfServiceFactory();
        $pdfService = $factory(
            $containerProphecy->reveal(),
            $this->prophesize(RequestedEntry::class)->reveal()
        );

        $this->assertInstanceOf(PdfService::class, $pdfService);
    }

    #[Test]
    public function it_needs_a_configuration_array_and_fails_if_not_there(): void
    {
        $config = [];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);

        $factory = new PdfServiceFactory();

        $this->expectException(RuntimeException::class);
        $pdfService = $factory(
            $containerProphecy->reveal(),
            $this->prophesize(RequestedEntry::class)->reveal()
        );
    }

    #[Test]
    public function it_needs_a_configuration_value_and_fails_if_not_there(): void
    {
        $config = [
            'pdf' => [],
        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);

        $factory = new PdfServiceFactory();

        $this->expectException(RuntimeException::class);
        $pdfService = $factory(
            $containerProphecy->reveal(),
            $this->prophesize(RequestedEntry::class)->reveal()
        );
    }
}
