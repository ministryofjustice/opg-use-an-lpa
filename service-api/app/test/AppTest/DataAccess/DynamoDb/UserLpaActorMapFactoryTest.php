<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\DynamoDb\UserLpaActorMapFactory;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Exception;

class UserLpaActorMapFactoryTest extends TestCase
{
    /** @test */
    public function can_instantiate()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([
            'repositories' => [
                'dynamodb' => [
                    'user-lpa-actor-map' => 'test-table'
                ]
            ]
        ]);

        $factory = new UserLpaActorMapFactory();
        $repo = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(UserLpaActorMap::class, $repo);
    }

    /** @test */
    public function cannot_instantiate()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new UserLpaActorMapFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('UserLpaActorMap table configuration not present');

        $factory($containerProphecy->reveal());
    }
}
