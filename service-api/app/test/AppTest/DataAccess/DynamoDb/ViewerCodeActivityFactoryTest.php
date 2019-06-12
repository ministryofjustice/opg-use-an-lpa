<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ViewerCodeActivity;
use App\DataAccess\DynamoDb\ViewerCodeActivityFactory;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ViewerCodeActivityFactoryTest extends TestCase
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
                    'viewer-activity-table' => 'test-table'
                ]
            ]
        ]);

        $factory = new ViewerCodeActivityFactory();
        $repo = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(ViewerCodeActivity::class, $repo);
    }

    public function testInvalidConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new ViewerCodeActivityFactory();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp( '/Viewer Activity table configuration not present/' );

        $factory($containerProphecy->reveal());
    }

}
