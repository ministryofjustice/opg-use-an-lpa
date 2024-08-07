<?php

declare(strict_types=1);

namespace AppTest\Service\Email;

use App\Service\Email\EmailClient;
use App\Service\Email\EmailClientFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class EmailClientFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function can_create_an_instance_of_the_email_client(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $key               = 'notreal_key_testingtestin-12345678-1234-4321-abcd-123456789012-12345678-1234-4321-abcd-123456789012';

        $containerProphecy->get('config')
            ->willReturn(
                [
                    'notify' => [
                        'api' => [
                            'key' => $key,
                        ],
                    ],
                ]
            );

        $httpClientPropercy = $this->prophesize(ClientInterface::class);

        $containerProphecy->get(ClientInterface::class)
            ->willReturn($httpClientPropercy->reveal());

        $factory = new EmailClientFactory();

        $emailClient = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(EmailClient::class, $emailClient);
    }

    #[Test]
    public function throws_exception_when_missing_configuration(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn([]);

        $factory = new EmailClientFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing notify API key');
        $emailClient = $factory($containerProphecy->reveal());
    }
}
