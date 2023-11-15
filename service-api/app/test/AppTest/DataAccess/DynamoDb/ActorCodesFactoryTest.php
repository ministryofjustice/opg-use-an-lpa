<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorCodes;
use App\DataAccess\DynamoDb\ActorCodesFactory;
use Aws\DynamoDb\DynamoDbClient;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class ActorCodesFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function can_instantiate(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([
            'repositories' => [
                'dynamodb' => [
                    'actor-codes-table' => 'test-table',
                ],
            ],
        ]);

        $factory = new ActorCodesFactory();
        $repo    = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(ActorCodes::class, $repo);
    }

    /** @test */
    public function cannot_instantiate(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new ActorCodesFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Actor Codes table configuration not present');

        $factory($containerProphecy->reveal());
    }
}
