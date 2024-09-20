<?php

declare(strict_types=1);

namespace CommonTest\Service\ApiClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Common\Service\ApiClient\Client;
use Common\Service\ApiClient\ClientFactory;
use Common\Service\Log\RequestTracing;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

#[CoversClass(ClientFactory::class)]
class ClientFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
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

        $factory = new ClientFactory();

        $this->expectException(RuntimeException::class);
        $client = $factory($containerProphecy->reveal());
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
