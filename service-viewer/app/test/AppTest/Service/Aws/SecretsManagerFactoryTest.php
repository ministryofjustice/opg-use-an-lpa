<?php

declare(strict_types=1);

namespace AppTest\Service\Aws;

use Aws\Sdk;
use Aws\SecretsManager\SecretsManagerClient;
use App\Service\Aws\SecretsManagerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SecretsManagerFactoryTest extends TestCase
{

    public function testValidConfig()
    {
        $container = $this->prophesize(ContainerInterface::class);

        // Use a real Aws\Sdk to sense check the method.
        $container->get(Sdk::class)->willReturn(new Sdk([
            'region'    => 'eu-west-1',
            'version'   => 'latest',
        ]));

        //---

        $factory = new SecretsManagerFactory();
        $client = $factory($container->reveal());

        $this->assertInstanceOf(SecretsManagerClient::class, $client);
    }

}