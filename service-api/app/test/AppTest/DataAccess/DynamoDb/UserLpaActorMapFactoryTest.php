<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\DynamoDb\UserLpaActorMapFactory;
use Aws\DynamoDb\DynamoDbClient;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class UserLpaActorMapFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function can_instantiate(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get(LoggerInterface::class)->willReturn(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([
            'repositories' => [
                'dynamodb' => [
                    'user-lpa-actor-map' => 'test-table',
                ],
            ],
        ]);

        $factory = new UserLpaActorMapFactory();
        $repo    = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(UserLpaActorMap::class, $repo);
    }

    /** @test */
    public function cannot_instantiate(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get(LoggerInterface::class)->willReturn(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new UserLpaActorMapFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('UserLpaActorMap table configuration not present');

        $factory($containerProphecy->reveal());
    }
}
