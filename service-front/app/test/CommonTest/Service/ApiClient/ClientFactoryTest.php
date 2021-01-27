<?php

declare(strict_types=1);

namespace CommonTest\Service\ApiClient;

use Common\Service\ApiClient\Client;
use Common\Service\ApiClient\ClientFactory;
use Common\Service\Log\RequestTracing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

/**
 * Class ClientFactoryTest
 *
 * @coversDefaultClass \Common\Service\ApiClient\ClientFactory
 *
 * @package CommonTest\Service\ApiClient
 */
class ClientFactoryTest extends TestCase
{
    /**
     * @test
     * @covers ::__invoke
     */
    public function it_creates_an_apiclient(): void
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
        $containerProphecy->get(ClientInterface::class)
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ClientInterface::class)->reveal());
        $containerProphecy->get(RequestTracing::TRACE_PARAMETER_NAME)
            ->shouldBeCalled()
            ->willReturn('a-trace-id');

        $factory = new ClientFactory();

        $client = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(Client::class, $client);
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

        $factory = new ClientFactory();

        $this->expectException(RuntimeException::class);
        $client = $factory($containerProphecy->reveal());
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
