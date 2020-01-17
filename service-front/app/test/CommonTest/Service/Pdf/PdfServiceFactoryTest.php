<?php

declare(strict_types=1);

namespace CommonTest\Service\Pdf;

use Common\Service\Pdf\PdfService;
use Common\Service\Pdf\PdfServiceFactory;
use Common\Service\Pdf\StylesService;
use DI\Factory\RequestedEntry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class PdfServiceFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_a_configured_pdf_service() {
        $config = [
            'pdf' => [
                'uri' => 'test'
            ]
        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);

        $templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $containerProphecy->get(TemplateRendererInterface::class)->willReturn($templateRendererProphecy->reveal());
        $clientProphecy = $this->prophesize(ClientInterface::class);
        $containerProphecy->get(ClientInterface::class)->willReturn($clientProphecy->reveal());
        $stylesProphecy = $this->prophesize(StylesService::class);
        $containerProphecy->get(StylesService::class)->willReturn($stylesProphecy->reveal());

        $factory = new PdfServiceFactory();
        $pdfService = $factory(
            $containerProphecy->reveal(),
            $this->prophesize(RequestedEntry::class)->reveal()
        );

        $this->assertInstanceOf(PdfService::class, $pdfService);
    }

    /** @test */
    public function it_needs_a_configuration_array_and_fails_if_not_there() {
        $config = [
        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);

        $factory = new PdfServiceFactory();

        $this->expectException(\RuntimeException::class);
        $pdfService = $factory(
            $containerProphecy->reveal(),
            $this->prophesize(RequestedEntry::class)->reveal()
        );
    }

    /** @test */
    public function it_needs_a_configuration_value_and_fails_if_not_there() {
        $config = [
            'pdf' => []
        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn($config);

        $factory = new PdfServiceFactory();

        $this->expectException(\RuntimeException::class);
        $pdfService = $factory(
            $containerProphecy->reveal(),
            $this->prophesize(RequestedEntry::class)->reveal()
        );
    }
}
