<?php

declare(strict_types=1);

namespace CommonTest\Service\Email;

use Common\Service\Email\EmailClient;
use Common\Service\Email\EmailClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;

class EmailClientFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_an_instance_of_the_email_client()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get('config')
            ->willReturn(
                [
                    'notify' => [
                        'api' => [
                            'key' => 'notreal_key_testingtestin-12345678-1234-4321-abcd-123456789012-12345678-1234-4321-abcd-123456789012',
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

    /**
     * @test
     */
    public function throws_exception_when_missing_configuration()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')->willReturn([]);

        $factory = new EmailClientFactory();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing notify API key');
        $emailClient = $factory($containerProphecy->reveal());
    }
}
