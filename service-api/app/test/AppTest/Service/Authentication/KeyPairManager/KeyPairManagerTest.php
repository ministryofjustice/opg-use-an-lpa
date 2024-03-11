<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication\KeyPairManager;

use App\Service\Authentication\KeyPairManager\KeyPair;
use App\Service\Authentication\KeyPairManager\KeyPairManagerInterface;
use App\Service\Authentication\KeyPairManager\OneLoginIdentityKeyPairManager;
use App\Service\Authentication\KeyPairManager\OneLoginUserInfoKeyPairManager;
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

    public function setUp(): void
    {
        $this->secretsManagerClient = $this->prophesize(SecretsManagerClient::class);
        $this->logger               = $this->prophesize(LoggerInterface::class);
    }

    /**
     * @template T of KeyPairManagerInterface
     * @return array{
     *     type: class-string<T>,
     *     algorithm: string,
     *     public: string,
     *     private?: string,
     * }
     */
    public function keyPairManagerTypes(): array
    {
        return [
            'OneLoginIdentityKeyPairManager' => [
                'type'      => OneLoginIdentityKeyPairManager::class,
                'algorithm' => 'RS256',
                'public'    => 'gov_uk_onelogin_identity_public_key',
                'private'   => 'gov_uk_onelogin_identity_private_key',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider keyPairManagerTypes
     * @template T of KeyPairManagerInterface
     * @psalm-param class-string<T> $type
     */
    public function can_instantiate(string $type, string $algorithm, string $public, ?string $private = null): void
    {
        $keyPairManager = new $type($this->secretsManagerClient->reveal(), $this->logger->reveal());
        $this->assertInstanceOf(KeyPairManagerInterface::class, $keyPairManager);

        $this->assertEquals($public, $keyPairManager::PUBLIC_KEY);
        $this->assertEquals($algorithm, $keyPairManager->getAlgorithm());

        $private === null ?: $this->assertEquals($private, $keyPairManager::PRIVATE_KEY);
    }

    /**
     * @test
     * @dataProvider keyPairManagerTypes
     * @template T of KeyPairManagerInterface
     * @psalm-param class-string<T> $type
     */
    public function get_key_pair(string $type, string $algorithm, string $public, ?string $private = null): void
    {
        $testPublicKey  = bin2hex(random_bytes(30));
        $testPrivateKey = bin2hex(random_bytes(30));

        $publicKeyResult = $this->prophesize(Result::class);
        $publicKeyResult->get('SecretString')->willReturn($testPublicKey);

        $privateKeyResult = $this->prophesize(Result::class);
        $privateKeyResult->get('SecretString')->willReturn($testPrivateKey);

        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => $public,
            ]
        )
            ->willReturn($publicKeyResult->reveal());

        if ($private !== null) {
            $this->secretsManagerClient->getSecretValue(
                [
                    'SecretId' => $private,
                ]
            )
                ->willReturn($privateKeyResult->reveal());
        }

        $keyPairManager = new $type($this->secretsManagerClient->reveal(), $this->logger->reveal());
        $keyPair        = $keyPairManager->getKeyPair();

        $this->assertInstanceOf(KeyPair::class, $keyPair);
        $this->assertEquals($testPublicKey, $keyPair->public);

        if ($private !== null) {
            $this->assertEquals($testPrivateKey, $keyPair->private->getString());
        }
    }

    /**
     * @test
     * @dataProvider keyPairManagerTypes
     * @template T of KeyPairManagerInterface
     * @psalm-param class-string<T> $type
     */
    public function get_key_pair_fails_when_incorrect_secret(
        string $type,
        string $algorithm,
        string $public,
        ?string $private = null,
    ): void {
        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => $public,
            ]
        )
            ->willThrow($this->prophesize(SecretsManagerException::class)->reveal());


        $keyPairManager = new $type($this->secretsManagerClient->reveal(), $this->logger->reveal());
        $this->expectException(SecretsManagerException::class);
        $keyPairManager->getKeyPair();
    }

    /**
     * @test
     * @dataProvider keyPairManagerTypes
     * @template T of KeyPairManagerInterface
     * @psalm-param class-string<T> $type
     */
    public function get_key_pair_fails_when_null_values_returned(
        string $type,
        string $algorithm,
        string $public,
        ?string $private = null,
    ): void {
        $publicKeyResult = $this->prophesize(Result::class);
        $publicKeyResult->get('SecretString')->willReturn(null)->shouldBeCalled();

        if ($private !== null) {
            $privateKeyResult = $this->prophesize(Result::class);
            $privateKeyResult->get('SecretString')->willReturn(null)->shouldBeCalled();
        }

        $this->secretsManagerClient->getSecretValue(
            [
                'SecretId' => $public,
            ]
        )
            ->willReturn($publicKeyResult->reveal())
            ->shouldBeCalled();

        if ($private !== null) {
            $this->secretsManagerClient->getSecretValue(
                [
                    'SecretId' => $private,
                ]
            )
                ->willReturn($privateKeyResult->reveal())
                ->shouldBeCalled();
        }

        $keyPairManager = new $type($this->secretsManagerClient->reveal(), $this->logger->reveal());
        $this->expectException(RuntimeException::class);
        $keyPairManager->getKeyPair();
    }
}
