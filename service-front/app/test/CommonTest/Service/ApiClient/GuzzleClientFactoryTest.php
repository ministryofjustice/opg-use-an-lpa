<?php

declare(strict_types=1);

namespace CommonTest\Service\ApiClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Common\Service\ApiClient\GuzzleClientFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use RuntimeException;

#[CoversClass(GuzzleClientFactory::class)]
class GuzzleClientFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_creates_a_http_client(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get('config')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'api' => [
                        'uri' => 'http://localhost',
                    ],
                ]
            );

        $factory = new GuzzleClientFactory();

        $httpClient = $factory($containerProphecy->reveal());
    }

    /**
     * @param array $configuration Bad configuration data for factory
     */
    #[DataProvider('badConfigurationData')]
    #[Test]
    public function it_throws_a_configuration_exception_when_missing(array $configuration): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get('config')
            ->shouldBeCalled()
            ->willReturn($configuration);

        $factory = new GuzzleClientFactory();

        $this->expectException(RuntimeException::class);
        $httpClient = $factory($containerProphecy->reveal());
    }

    public static function badConfigurationData(): array
    {
        return [
            [
                [],
            ],
            [
                ['api' => []],
            ],
        ];
    }
}
