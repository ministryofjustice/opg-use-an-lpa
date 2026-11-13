<?php

declare(strict_types=1);

namespace CommonTest\Service\Pdf;

use Common\Service\Pdf\PdfService;
use Common\Service\Pdf\PdfServiceFactory;
use Common\Service\Pdf\StylesService;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass(PdfServiceFactory::class)]
#[UsesClass(PdfService::class)]
final class PdfServiceFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_a_configured_pdf_service(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(5))->method('get')->willReturnMap([
            [
                'config',
                ['pdf' => ['uri' => 'http://pdf-service:8080']],
            ],
            [
                TemplateRendererInterface::class,
                $this->createStub(TemplateRendererInterface::class),
            ],
            [
                ClientInterface::class,
                $this->createStub(ClientInterface::class),
            ],
            [
                StylesService::class,
                $this->createStub(StylesService::class),
            ],
            [
                LoggerInterface::class,
                $this->createStub(LoggerInterface::class),
            ],
        ]);

        $factory = new PdfServiceFactory();

        self::assertInstanceOf(PdfService::class, $factory($container));
    }

    #[Test]
    public function it_needs_a_configuration_array_and_fails_if_not_there(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('config')->willReturn([]);

        $factory = new PdfServiceFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PDF configuration missing');
        $factory($container);
    }

    #[Test]
    public function it_needs_a_configuration_value_and_fails_if_not_there(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('config')->willReturn(['pdf' => []]);

        $factory = new PdfServiceFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing API configuration: uri');
        $factory($container);
    }
}
