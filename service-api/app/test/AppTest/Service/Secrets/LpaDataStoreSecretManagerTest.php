<?php

declare(strict_types=1);

namespace AppTest\Service\Secrets;

use App\Service\Secrets\LpaDataStoreSecretManager;
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

    public function setUp(): void
    {
        $this->secretsManagerClient = $this->prophesize(SecretsManagerClient::class);
        $this->logger               = $this->prophesize(LoggerInterface::class);
    }

    #[Test]
    public function gets_key_pair(): void
    {
        $mockSecretKey = 'my-secret-key';

        $mockSecretKeyResult = $this->prophesize(Result::class);
        $mockSecretKeyResult->get('SecretString')->willReturn($mockSecretKey);

        $this->secretsManagerClient->getSecretValue(
            [
                'SecretName' => LpaDataStoreSecretManager::SECRET_NAME,
            ]
        )
            ->willReturn($mockSecretKeyResult->reveal());

        $lpaDataStoreSecretManager = new LpaDataStoreSecretManager(
            $this->secretsManagerClient->reveal(),
            $this->logger->reveal()
        );

        $this->assertEquals($mockSecretKey, $lpaDataStoreSecretManager->getSecret());
    }

    #[Test]
    public function gets_key_pair_when_null(): void
    {
        $mockSecretKey = null;

        $mockSecretKeyResult = $this->prophesize(Result::class);
        $mockSecretKeyResult->get('SecretString')->willReturn($mockSecretKey);

        $this->secretsManagerClient->getSecretValue(
            [
                'SecretName' => LpaDataStoreSecretManager::SECRET_NAME,
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
    public function gets_key_pair_when_aws_has_error(): void
    {
        $this->secretsManagerClient->getSecretValue(
            [
                'SecretName' => LpaDataStoreSecretManager::SECRET_NAME,
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
}
