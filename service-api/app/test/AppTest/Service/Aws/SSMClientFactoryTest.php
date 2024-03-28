<?php

declare(strict_types=1);

namespace AppTest\Service\Aws;

use App\Service\Aws\SSMClientFactory;
use Aws\Sdk;
use Aws\Ssm\SsmClient;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\MethodCannotBeConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameAlreadyConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameNotConfiguredException;
use PHPUnit\Framework\MockObject\MethodParametersAlreadyConfiguredException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class SSMClientFactoryTest extends TestCase
{
    use ProphecyTrait;

    private SSMClientFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SSMClientFactory();
    }

    /**
     * @throws MethodCannotBeConfiguredException
     * @throws ContainerExceptionInterface
     * @throws MethodNameNotConfiguredException
     * @throws MethodParametersAlreadyConfiguredException
     * @throws NotFoundExceptionInterface
     * @throws ExpectationFailedException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws MethodNameAlreadyConfiguredException
     * @throws \PHPUnit\Framework\Exception
     * @throws IncompatibleReturnValueException
     */
    public function testInvokeReturnsSsmClient(): void
    {
        $sdkProphecy = $this->prophesize(Sdk::class);
        $sdkProphecy->createSsm()
            ->willReturn($this->prophesize(SsmClient::class)->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(Sdk::class)
            ->willReturn($sdkProphecy->reveal());

        $result = ($this->factory)($containerProphecy->reveal());

        $this->assertInstanceOf(SsmClient::class, $result);
    }
}
