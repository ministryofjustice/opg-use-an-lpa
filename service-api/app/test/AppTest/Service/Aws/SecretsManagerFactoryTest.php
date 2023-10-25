<?php

declare(strict_types=1);

namespace AppTest\Service\Aws;

use App\Service\Aws\SecretsManagerFactory;
use Aws\Sdk;
use Aws\SecretsManager\SecretsManagerClient;
use Monolog\Test\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class SecretsManagerFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_creates_a_SecretsManager(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(Sdk::class)
            ->willReturn(new Sdk([
                 'region'  => 'eu-west-1',
                 'version' => 'latest',
            ]));

        $factory = new SecretsManagerFactory();
        $client  = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(SecretsManagerClient::class, $client);
    }
}
