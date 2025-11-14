<?php

declare(strict_types=1);

namespace AppTest\Service\SystemMessage;

use App\Service\SystemMessage\SystemMessageFactory;
use Aws\Ssm\SsmClient;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use PHPUnit\Event\NoPreviousThrowableException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class SystemMessageFactoryTest extends TestCase
{
    /**
     * @var SsmClient&MockObject
     */
    use ProphecyTrait;

    private MockObject $container;

    private MockObject $ssmClient;

    /**
     * @throws NoPreviousThrowableException
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->ssmClient = $this->createMock(SsmClient::class);

        parent::setUp();
    }

    /**
     * @throws ServiceNotCreatedException
     * @throws ExpectationFailedException
     * @throws ContainerExceptionInterface
     * @throws \PHPUnit\Framework\Exception
     */
    #[Test]
    public function createsSystemMessageService(): void
    {
        $systemMessageFactory = new SystemMessageFactory();

        $valueMap = [
          ['config', ['environment_name' => '']],
          [SsmClient::class, $this->ssmClient],
        ];

        $this->container->method('get')->willReturnMap($valueMap);

        $systemMessageService = $systemMessageFactory($this->container, 'systemMessageFactory');

        $this->assertSame('/system-message/', $systemMessageService->getPrefix());
    }

    /**
     * @throws ServiceNotCreatedException
     * @throws ExpectationFailedException
     * @throws ContainerExceptionInterface
     * @throws \PHPUnit\Framework\Exception
     */
    #[Test]
    public function createsEnvironmentPrefix(): void
    {
        $systemMessageFactory = new SystemMessageFactory();

        $valueMap = [
            ['config', ['environment_name' => 'production']],
            [SsmClient::class, $this->ssmClient],
        ];

        $this->container->method('get')->willReturnMap($valueMap);

        $systemMessageService = $systemMessageFactory($this->container, 'systemMessageFactory');

        $this->assertSame('/system-message/production/', $systemMessageService->getPrefix());
    }
}
