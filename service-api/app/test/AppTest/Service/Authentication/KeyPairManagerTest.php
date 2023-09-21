<?php

namespace AppTest\Service\Authentication;

use App\Service\Authentication\KeyPair;
use App\Service\Authentication\KeyPairManager;
use Aws\Result;
use Aws\SecretsManager\SecretsManagerClient;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class KeyPairManagerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|SecretsManagerClient $secretsManagerClient;
    private ObjectProphecy|LoggerInterface $logger;
    const PUBLIC_KEY = 'gov_uk_onelogin_identity_public_key';
    const PRIVATE_KEY = 'gov_uk_onelogin_identity_private_key';

    public function setUp(): void
    {
        // Constructor arguments
        $this->smClient     = $this->prophesize(SecretsManagerClient::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    /** @test */
    public function can_initiate(){
        $keyPairManager = new KeyPairManager($this->smClient->reveal(), $this->logger->reveal());
        $this->assertInstanceOf(KeyPairManager::class, $keyPairManager);
    }


    /** @test */
    public function get_key_pair(){
        $testPublicKey = bin2hex(random_bytes(30));
        $testPrivateKey = bin2hex(random_bytes(30));

        $publicKeyResult = $this->prophesize(Result::class);
        $publicKeyResult->get('SecretString')->willReturn($testPublicKey)->shouldBeCalled();
        $publicKeyResult->reveal();

        $privateKeyResult = $this->prophesize(Result::class);
        $privateKeyResult->get('SecretString')->willReturn($testPrivateKey)->shouldBeCalled();
        $privateKeyResult->reveal();

        $this->smClient->getSecretValue(['SecretId' => self::PUBLIC_KEY])->willReturn($publicKeyResult)->shouldBeCalled();
        $this->smClient->getSecretValue(['SecretId' => self::PRIVATE_KEY])->willReturn($privateKeyResult)->shouldBeCalled();

        $keyPairManager = new KeyPairManager($this->smClient->reveal(), $this->logger->reveal());
        $keyPair = $keyPairManager->getKeyPair();

        $testPrivateKey = new HiddenString($testPrivateKey, true, false);

        $this->assertInstanceOf(KeyPair::class, $keyPair);
        $this->assertEquals($testPublicKey, $keyPair->public);
        $this->assertEquals($testPrivateKey, $keyPair->private);
    }
}
