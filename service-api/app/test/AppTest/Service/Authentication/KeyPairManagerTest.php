<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\KeyManager\KeyPair;
use App\Service\Authentication\KeyManager\KeyPairManager;
use Aws\Result;
use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use RuntimeException;

class KeyPairManagerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|SecretsManagerClient $secretsManagerClient;
    private ObjectProphecy|LoggerInterface $logger;

    public const PUBLIC_KEY  = 'gov_uk_onelogin_identity_public_key';
    public const PRIVATE_KEY = 'gov_uk_onelogin_identity_private_key';

    public function setUp(): void
    {
        $this->secretsManagerClient = $this->prophesize(SecretsManagerClient::class);
        $this->logger               = $this->prophesize(LoggerInterface::class);
    }

    /** @test */
    public function can_initiate(): void
    {
        $keyPairManager = new KeyPairManager($this->secretsManagerClient->reveal(), $this->logger->reveal());
        $this->assertInstanceOf(KeyPairManager::class, $keyPairManager);
        $this->assertEquals(self::PUBLIC_KEY, $keyPairManager::PUBLIC_KEY);
        $this->assertEquals(self::PRIVATE_KEY, $keyPairManager::PRIVATE_KEY);
    }

    /** @test */
    public function get_key_pair(): void
    {
        $testPublicKey  = bin2hex(random_bytes(30));
        $testPrivateKey = bin2hex(random_bytes(30));

        $publicKeyResult = $this->prophesize(Result::class);
        $publicKeyResult->get('SecretString')->willReturn($testPublicKey)->shouldBeCalled();

        $privateKeyResult = $this->prophesize(Result::class);
        $privateKeyResult->get('SecretString')->willReturn($testPrivateKey)->shouldBeCalled();


        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => self::PUBLIC_KEY,
            ]
        )
            ->willReturn($publicKeyResult->reveal())
            ->shouldBeCalled();
        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => self::PRIVATE_KEY,
            ]
        )
            ->willReturn($privateKeyResult->reveal())
            ->shouldBeCalled();

        $keyPairManager = new KeyPairManager($this->secretsManagerClient->reveal(), $this->logger->reveal());
        $keyPair        = $keyPairManager->getKeyPair();

        $this->assertInstanceOf(KeyPair::class, $keyPair);
        $this->assertEquals($testPublicKey, $keyPair->public);
        $this->assertEquals($testPrivateKey, $keyPair->private->getString());
    }

    /** @test */
    public function get_key_pair_fails_when_incorrect_secret(): void
    {
        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => self::PUBLIC_KEY,
            ]
        )
            ->willThrow($this->prophesize(SecretsManagerException::class)->reveal());


        $keyPairManager = new KeyPairManager($this->secretsManagerClient->reveal(), $this->logger->reveal());
        $this->expectException(SecretsManagerException::class);
        $keyPairManager->getKeyPair();
    }

    /**
     * Provides public key and private key combinations to test null handling
     *
     * @return array
     */
    public function secretProvider(): array
    {
        return [
            [null, 'privateKey'],
            ['publicKey', null],
            [null, null],
        ];
    }

    /**
     * @test
     * @dataProvider secretProvider
     */
    public function get_key_pair_fails_when_null_values_returned(?string $publicKey, ?string $privateKey): void
    {
        $publicKeyResult = $this->prophesize(Result::class);
        $publicKeyResult->get('SecretString')->willReturn($publicKey)->shouldBeCalled();

        $privateKeyResult = $this->prophesize(Result::class);
        $privateKeyResult->get('SecretString')->willReturn($privateKey)->shouldBeCalled();


        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => self::PUBLIC_KEY,
            ]
        )
            ->willReturn($publicKeyResult->reveal())
            ->shouldBeCalled();
        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => self::PRIVATE_KEY,
            ]
        )
            ->willReturn($privateKeyResult->reveal())
            ->shouldBeCalled();

        $keyPairManager = new KeyPairManager($this->secretsManagerClient->reveal(), $this->logger->reveal());
        $this->expectException(RuntimeException::class);
        $keyPairManager->getKeyPair();
    }
}
