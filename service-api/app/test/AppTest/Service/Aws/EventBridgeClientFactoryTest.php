<?php

declare(strict_types=1);

namespace AppTest\Service\Aws;

use App\Service\Aws\EventBridgeClientFactory;
use Aws\EventBridge\EventBridgeClient;
use Aws\Sdk;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class EventBridgeClientFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_creates_a_dbclient(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(Sdk::class)
            ->willReturn(new Sdk([
                'region'  => 'eu-west-1',
                'version' => 'latest',
            ]));

        $factory = new EventBridgeClientFactory();
        $client  = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(EventBridgeClient::class, $client);
    }
}
