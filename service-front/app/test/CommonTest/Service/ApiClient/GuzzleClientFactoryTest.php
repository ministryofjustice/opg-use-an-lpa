<?php

declare(strict_types=1);

namespace CommonTest\Service\ApiClient;

use Common\Service\ApiClient\GuzzleClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Class GuzzleClientFactoryTest
 *
 * @coversDefaultClass \Common\Service\ApiClient\GuzzleClientFactory
 *
 * @package CommonTest\Service\ApiClient
 */
class GuzzleClientFactoryTest extends TestCase
{
    /**
     * @test
     * @covers ::__invoke
     */
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
     * @test
     * @covers ::__invoke
     * @dataProvider badConfigurationData
     * @param array $configuration Bad configuration data for factory
     */
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

    public function badConfigurationData(): array
    {
        return [
            [
                []
            ],
            [
                ['api' => []],
            ]
        ];
    }
}
