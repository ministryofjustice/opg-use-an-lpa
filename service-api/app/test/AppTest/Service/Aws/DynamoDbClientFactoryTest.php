<?php

declare(strict_types=1);

namespace AppTest\Service\Aws;

use App\Service\Aws\DynamoDbClientFactory;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DynamoDbClientFactoryTest extends TestCase
{
    public function testMissingConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing aws configuration');

        $containerProphecy
            ->get('config')
            ->willReturn([]);

        $factory = new DynamoDbClientFactory();

        $factory($containerProphecy->reveal());
    }

    public function testValidConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy
            ->get('config')
            ->willReturn([
                'aws' => [
                    'dynamodb' => [
                        'region'    => 'eu-west-1',
                        'version'   => 'latest',
                    ],
                ],
            ]);

        $factory = new DynamoDbClientFactory();

        $dynamoDbClient = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(DynamoDbClient::class, $dynamoDbClient);
    }
}
