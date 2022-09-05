<?php

declare(strict_types=1);

namespace AppTest\Service\Aws;

use App\Service\Aws\DynamoDbClientFactory;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Sdk;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class DynamoDbClientFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_creates_a_dbclient() :void
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
