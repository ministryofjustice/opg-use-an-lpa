<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorUsers;
use App\DataAccess\DynamoDb\ActorUsersFactory;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ActorUsersFactoryTest extends TestCase
{
    /** @test */
    public function it_returns_an_actor_user_repository()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([
            'repositories' => [
                'dynamodb' => [
                    'actor-users-table' => 'test-table'
                ]
            ]
        ]);

        $factory = new ActorUsersFactory();
        $repo = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(ActorUsers::class, $repo);
    }

    public function it_throws_an_exception_when_not_configured_correctly()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new ActorUsersFactory();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Actor Users table configuration not present');

        $factory($containerProphecy->reveal());
    }

}
