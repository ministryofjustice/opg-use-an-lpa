<?php

declare(strict_types=1);

namespace CommonTest\Service\Aws;

use Common\Service\Aws\SecretsManagerFactory;
use Aws\Sdk;
use Aws\SecretsManager\SecretsManagerClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SecretsManagerFactoryTest extends TestCase
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

        $factory = new SecretsManagerFactory();
        $client = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(SecretsManagerClient::class, $client);
    }
}
