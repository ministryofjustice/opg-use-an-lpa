<?php

declare(strict_types=1);

namespace CommonTest\Service\SystemMessage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Common\Service\ApiClient\Client;
use Common\Service\SystemMessage\SystemMessageService;
use Common\Service\SystemMessage\SystemMessageServiceFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Exception\Prophecy\ObjectProphecyException;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

#[CoversClass(SystemMessageServiceFactory::class)]
class SystemMessageServiceFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws ObjectProphecyException
     */
    #[Test]
    public function it_will_create_an_instance(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $apiClientProphecy = $this->prophesize(Client::class);

        $containerProphecy->get(Client::class)->willReturn($apiClientProphecy->reveal());

        $systemMessageServiceFactory = new SystemMessageServiceFactory();

        $systemMessageService = $systemMessageServiceFactory($containerProphecy->reveal());

        $this->assertInstanceOf(SystemMessageService::class, $systemMessageService);
    }
}
