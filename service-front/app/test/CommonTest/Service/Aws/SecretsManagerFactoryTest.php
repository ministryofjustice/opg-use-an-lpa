<?php

declare(strict_types=1);

namespace CommonTest\Service\Aws;

use Aws\Sdk;
use Aws\SecretsManager\SecretsManagerClient;
use Common\Service\Aws\SecretsManagerFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class SecretsManagerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testValidConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        // Use a real Aws\Sdk to sense check the method.
        $containerProphecy->get(Sdk::class)
            ->willReturn(new Sdk([
                'region'  => 'eu-west-1',
                'version' => 'latest',
            ]));

        //---

        $factory = new SecretsManagerFactory();
        $client  = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(SecretsManagerClient::class, $client);
    }
}
