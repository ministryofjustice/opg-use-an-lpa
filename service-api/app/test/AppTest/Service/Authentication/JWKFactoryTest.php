<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication;

use App\Service\Authentication\JWKFactory;
use App\Service\Authentication\KeyPairManager\KeyPair;
use App\Service\Authentication\KeyPairManager\OneLoginIdentityKeyPairManager;
use InvalidArgumentException;
use Jose\Component\Core\JWK;
use ParagonIE\HiddenString\HiddenString;
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

    /** @test */
    public function can_create_jwk(): void
    {
        $JWK = (new JWKFactory())($this->keyPairManager->reveal());
        self::assertInstanceOf(JWK::class, $JWK);
        self::assertTrue($JWK->has('alg'));
        self::assertTrue($JWK->has('use'));
        self::assertEquals('RS256', $JWK->get('alg'));
        self::assertEquals('sig', $JWK->get('use'));
    }
}
