<?php

declare(strict_types=1);

namespace ViewerTest\Service\Aws;

use Aws\Sdk;
use Aws\SecretsManager\SecretsManagerClient;
use Viewer\Service\Aws\SecretsManagerFactory;
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
