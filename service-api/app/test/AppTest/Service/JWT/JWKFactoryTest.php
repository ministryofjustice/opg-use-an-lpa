<?php

declare(strict_types=1);

namespace AppTest\Service\JWT;

use App\Service\JWT\JWKFactory;
use App\Service\Secrets\KeyPair;
use App\Service\Secrets\OneLoginIdentityKeyPairManager;
use App\Service\Secrets\Secret;
use App\Service\Secrets\SecretManagerInterface;
use InvalidArgumentException;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class JWKFactoryTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|OneLoginIdentityKeyPairManager $keyPairManager;

    public function setUp(): void
    {
        $key = openssl_pkey_new(
            [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );
        if ($key === false) {
            throw new InvalidArgumentException('Unable to create the key');
        }
        $details = openssl_pkey_get_details($key);
        if (! is_array($details)) {
            throw new InvalidArgumentException('Unable to get key details');
        }

        $key1    = '';
        $success = openssl_pkey_export($key, $key1);

        if (!$success) {
            throw new InvalidArgumentException('Unable to export key to string');
        }
        $keyPair = new KeyPair('public', new HiddenString($key1, false, true));

        $this->keyPairManager = $this->prophesize(OneLoginIdentityKeyPairManager::class);
        $this->keyPairManager->getKeyPair()->willReturn($keyPair);
        $this->keyPairManager->getAlgorithm()->willReturn('RS256');
    }

    #[Test]
    public function can_create_an_async_keypair_jwk(): void
    {
        $jwk = (new JWKFactory())($this->keyPairManager->reveal());
        $this->assertTrue($jwk->has('alg'));
        $this->assertTrue($jwk->has('use'));
        $this->assertEquals('RS256', $jwk->get('alg'));
        $this->assertEquals('sig', $jwk->get('use'));
    }

    #[Test]
    public function can_create_a_shared_secret_jwk(): void
    {
        $secret        = new Secret(new HiddenString('test_secret'));
        $secretManager = $this->prophesize(SecretManagerInterface::class);
        $secretManager->getSecret()->willReturn($secret);
        $secretManager->getAlgorithm()->willReturn('HS256');

        $jwk = (new JWKFactory())($secretManager->reveal());
        $this->assertTrue($jwk->has('alg'));
        $this->assertTrue($jwk->has('use'));
        $this->assertEquals('HS256', $jwk->get('alg'));
        $this->assertEquals('sig', $jwk->get('use'));
        $this->assertEquals('dGVzdF9zZWNyZXQ', $jwk->get('k')); // Base64UrlEncoded 'test_secret'
    }
}
