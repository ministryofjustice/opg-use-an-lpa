<?php

declare(strict_types=1);

namespace AppTest\Service\Secrets;

use App\Service\Secrets\LpaDataStoreSecretManager;
use App\Service\Secrets\Secret;
use Aws\Result;
use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use RuntimeException;

class LpaDataStoreSecretManagerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|SecretsManagerClient $secretsManagerClient;

    private ObjectProphecy|LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->secretsManagerClient = $this->prophesize(SecretsManagerClient::class);
        $this->logger               = $this->prophesize(LoggerInterface::class);
    }

    #[Test]
    public function gets_secret(): void
    {
        $mockSecretKey = 'my-secret-key';

        $mockSecretKeyResult = $this->prophesize(Result::class);
        $mockSecretKeyResult->get('SecretString')->willReturn($mockSecretKey);

        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => LpaDataStoreSecretManager::SECRET_NAME,
            ]
        )
            ->willReturn($mockSecretKeyResult->reveal());

        $lpaDataStoreSecretManager = new LpaDataStoreSecretManager(
            $this->secretsManagerClient->reveal(),
            $this->logger->reveal()
        );

        $this->assertInstanceOf(Secret::class, $lpaDataStoreSecretManager->getSecret());
        $this->assertEquals($mockSecretKey, $lpaDataStoreSecretManager->getSecret()->secret);
    }

    #[Test]
    public function gets_secret_when_null(): void
    {
        $mockSecretKey = null;

        $mockSecretKeyResult = $this->prophesize(Result::class);
        $mockSecretKeyResult->get('SecretString')->willReturn($mockSecretKey);

        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => LpaDataStoreSecretManager::SECRET_NAME,
            ]
        )
            ->willReturn($mockSecretKeyResult->reveal());

        $lpaDataStoreSecretManager = new LpaDataStoreSecretManager(
            $this->secretsManagerClient->reveal(),
            $this->logger->reveal()
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Key could not be found.');

        $lpaDataStoreSecretManager->getSecret();
    }

    #[Test]
    public function throws_exception_when_aws_has_error(): void
    {
        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => LpaDataStoreSecretManager::SECRET_NAME,
            ]
        )
            ->willThrow(SecretsManagerException::class);

        $lpaDataStoreSecretManager = new LpaDataStoreSecretManager(
            $this->secretsManagerClient->reveal(),
            $this->logger->reveal()
        );

        $this->expectException(SecretsManagerException::class);

        $lpaDataStoreSecretManager->getSecret();
    }

    #[Test]
    public function has_the_correct_algorithm(): void
    {
        $lpaDataStoreSecretManager = new LpaDataStoreSecretManager(
            $this->secretsManagerClient->reveal(),
            $this->logger->reveal()
        );

        $alg = $lpaDataStoreSecretManager->getAlgorithm();

        $this->assertSame('HS256', $alg);
    }
}
