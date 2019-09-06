<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ViewerCodes;
use App\DataAccess\DynamoDb\ViewerCodesFactory;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Exception;

class ViewerCodesFactoryTest extends TestCase
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
                    'viewer-codes-table' => 'test-table'
                ]
            ]
        ]);

        $factory = new ViewerCodesFactory();
        $repo = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(ViewerCodes::class, $repo);
    }

    public function testInvalidConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new ViewerCodesFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Viewer Codes table configuration not present');

        $factory($containerProphecy->reveal());
    }
}
