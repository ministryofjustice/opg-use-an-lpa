<?php

declare(strict_types=1);

namespace ViewerTest\Service\Aws;

use Aws\DynamoDb\DynamoDbClient;
use Aws\Sdk;
use App\Service\Aws\DynamoDbClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DynamoDbClientFactoryTest extends TestCase
{
    public function testValidConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        // Use a real Aws\Sdk to sense check the method.
        $containerProphecy->get(Sdk::class)
            ->willReturn(new Sdk([
                'region'    => 'eu-west-1',
                'version'   => 'latest',
            ]));

        //---

        $factory = new DynamoDbClientFactory();
        $client = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(DynamoDbClient::class, $client);
    }
}
