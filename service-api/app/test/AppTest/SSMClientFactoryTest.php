<?php

declare(strict_types=1);

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\MethodCannotBeConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameAlreadyConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameNotConfiguredException;
use PHPUnit\Framework\MockObject\MethodParametersAlreadyConfiguredException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Aws\Sdk;
use Aws\Ssm\SsmClient;
use App\Service\Aws\SSMClientFactory;
use Psr\Container\NotFoundExceptionInterface;

class SSMClientFactoryTest extends TestCase
{
    private ContainerInterface $container;
    private SSMClientFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory   = new SSMClientFactory();
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
        $sdk       = $this->createMock(Sdk::class);
        $ssmClient = $this->createMock(SsmClient::class);

        $sdk->expects($this->once())
            ->method('createClient')
            ->with('Ssm')
            ->willReturn($ssmClient);

        $this->container->expects($this->once())
            ->method('get')
            ->with(Sdk::class)
            ->willReturn($sdk);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(SsmClient::class, $result);
    }
}
