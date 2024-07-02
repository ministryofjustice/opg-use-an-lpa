<?php

declare(strict_types=1);

namespace AppTest\Service\ApiClient;

use App\Service\ApiClient\ClientFactory;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class ClientFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function can_create_an_instance_of_a_client(): void
    {
        $factory = new ClientFactory();

        $client = $factory($this->prophesize(ContainerInterface::class)->reveal());

        $this->assertInstanceOf(Client::class, $client);
    }
}
