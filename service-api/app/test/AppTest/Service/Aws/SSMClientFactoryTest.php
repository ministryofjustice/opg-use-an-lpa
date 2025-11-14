<?php

declare(strict_types=1);

namespace AppTest\Service\Aws;

use App\Service\Aws\SSMClientFactory;
use Aws\Sdk;
use Aws\Ssm\SsmClient;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class SSMClientFactoryTest extends TestCase
{
    use ProphecyTrait;

    private SSMClientFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SSMClientFactory();
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function invokeReturnsSsmClient(): void
    {
        $sdkProphecy = $this->prophesize(Sdk::class);
        $sdkProphecy->createSsm()
            ->willReturn($this->prophesize(SsmClient::class)->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(Sdk::class)
            ->willReturn($sdkProphecy->reveal());

        ($this->factory)($containerProphecy->reveal());
    }
}
