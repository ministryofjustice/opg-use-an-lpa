<?php

declare(strict_types=1);

namespace CommonTest\Service\ApiClient;

use Common\Service\ApiClient\Client;
use Common\Service\ApiClient\ClientFactory;
use Common\Service\Context\UserContext;
use Common\Service\Log\RequestTracing;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

#[CoversClass(ClientFactory::class)]
#[UsesClass(Client::class)]
#[UsesClass(UserContext::class)]
class ClientFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_an_apiclient(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects(self::once())->method('sendRequest')
            ->with(self::callback(
                fn (RequestInterface $request): bool => $request->getHeaderLine('User-Token') === 'current-user'
            ))
            ->willReturn(new JsonResponse([]));

        $context   = new UserContext('current-user', 'current-session');
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(4))->method('get')->willReturnMap([
            [
                'config',
                ['api' => ['uri' => 'http://localhost']],
            ],
            [
                ClientInterface::class,
                $httpClient,
            ],
            [
                RequestTracing::TRACE_PARAMETER_NAME,
                'a-trace-id',
            ],
            [
                UserContext::class,
                $context,
            ],
        ]);

        $factory = new ClientFactory();

        $client = $factory($container);

        self::assertSame([], $client->httpGet('/lpas'));
    }

    /**
     * @param array $configuration Bad configuration data for factory
     */
    #[DataProvider('badConfigurationData')]
    #[Test]
    public function it_throws_a_configuration_exception_when_missing(array $configuration): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('config')->willReturn($configuration);

        $factory = new ClientFactory();

        $this->expectException(RuntimeException::class);
        $factory($container);
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
