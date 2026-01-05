<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\CombinedLpaManager;
use App\Service\Lpa\LpaManagerFactory;
use App\Service\Lpa\SiriusLpaManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LpaManagerFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_a_sirius_lpa_manager(): void
    {
        $mockFeatureEnabled = $this->createMock(FeatureEnabled::class);
        $mockFeatureEnabled->method('__invoke')->willReturn(false);

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->method('get')
            ->willReturnMap(
                [
                    [
                        FeatureEnabled::class,
                        $mockFeatureEnabled,
                    ],
                    [
                        SiriusLpaManager::class,
                        $this->createMock(SiriusLpaManager::class),
                    ],
                ]
            );

        $sut = new LpaManagerFactory($mockContainer);

        $manager = ($sut)();

        $this->assertInstanceOf(SiriusLpaManager::class, $manager);
    }

    #[Test]
    public function it_cannot_yet_create_a_combined_manager(): void
    {
        $mockFeatureEnabled = $this->createMock(FeatureEnabled::class);
        $mockFeatureEnabled->method('__invoke')->willReturn(true);

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->method('get')
            ->willReturnMap(
                [
                    [
                        FeatureEnabled::class,
                        $mockFeatureEnabled,
                    ],
                    [
                        CombinedLpaManager::class,
                        $this->createMock(CombinedLpaManager::class),
                    ],
                ]
            );

        $sut = new LpaManagerFactory($mockContainer);

        $manager = ($sut)();
        $this->assertInstanceOf(CombinedLpaManager::class, $manager);
    }
}
