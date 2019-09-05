<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorLpaCodes;
use App\DataAccess\DynamoDb\ActorLpaCodesFactory;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Exception;

class ActorLpaCodesFactoryTest extends TestCase
{
    public function testValidConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([
            'repositories' => [
                'dynamodb' => [
                    'actor-lpa-codes-table' => 'test-table'
                ]
            ]
        ]);

        $factory = new ActorLpaCodesFactory();
        $repo = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(ActorLpaCodes::class, $repo);
    }

    public function testInvalidConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new ActorLpaCodesFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Actor LPA Codes table configuration not present');

        $factory($containerProphecy->reveal());
    }
}
