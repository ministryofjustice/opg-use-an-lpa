<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ViewerCodeActivity;
use App\DataAccess\DynamoDb\ViewerCodeActivityFactory;
use Aws\DynamoDb\DynamoDbClient;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class ViewerCodeActivityFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testValidConfig(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([
            'repositories' => [
                'dynamodb' => [
                    'viewer-activity-table' => 'test-table',
                ],
            ],
        ]);

        $factory = new ViewerCodeActivityFactory();
        $repo    = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(ViewerCodeActivity::class, $repo);
    }

    public function testInvalidConfig(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(DynamoDbClient::class)->willReturn(
            $this->prophesize(DynamoDbClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new ViewerCodeActivityFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Viewer Activity table configuration not present');

        $factory($containerProphecy->reveal());
    }
}
