<?php

declare(strict_types=1);

namespace ViewerTest\Handler\Factory;

use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\LpaService;
use Common\Service\Pdf\PdfService;
use Common\Service\Security\RateLimitService;
use Common\Service\Security\RateLimitServiceFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Viewer\Handler\DownloadLpaHandler;
use Viewer\Handler\Factory\DownloadLpaHandlerFactory;

class DownloadLpaHandlerFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_returns_a_DownloadLpaHandler(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy
            ->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());

        $containerProphecy
            ->get(FeatureEnabled::class)
            ->willReturn($this->prophesize(FeatureEnabled::class)->reveal());

        $containerProphecy
            ->get(LpaService::class)
            ->willReturn($this->prophesize(LpaService::class)->reveal());

        $containerProphecy
            ->get(PdfService::class)
            ->willReturn($this->prophesize(PdfService::class)->reveal());

        $rateLimitService = $this->prophesize(RateLimitService::class);

        $rateLimitServiceFactory = $this->prophesize(RateLimitServiceFactory::class);
        $rateLimitServiceFactory
            ->factory('download_lpa')
            ->willReturn($rateLimitService->reveal());

        $containerProphecy
            ->get(RateLimitServiceFactory::class)
            ->willReturn($rateLimitServiceFactory->reveal());

        $factory = new DownloadLpaHandlerFactory();

        $handler = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(DownloadLpaHandler::class, $handler);
    }
}
