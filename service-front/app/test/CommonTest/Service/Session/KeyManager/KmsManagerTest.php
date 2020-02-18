<?php

declare(strict_types=1);

namespace CommonTest\Service\Session\KeyManager;

use Aws\Kms\Exception\KmsException;
use Aws\Kms\KmsClient;
use Aws\Result as AwsResult;
use Common\Service\Session\KeyManager\Key;
use Common\Service\Session\KeyManager\KeyCache;
use Common\Service\Session\KeyManager\KeyNotFoundException;
use Common\Service\Session\KeyManager\KmsManager;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class KmsManagerTest extends TestCase
{
    const TEST_KMS_CMK_ALIAS = 'test-alias-name';

    private $cacheProphecy;
    private $kmsClientProphecy;

    public function setUp(): void
    {
        // Constructor arguments
        $this->cacheProphecy = $this->prophesize(KeyCache::class);
        $this->kmsClientProphecy = $this->prophesize(KmsClient::class);
    }

    public function testCanInstantiate()
    {
        $manager = new KmsManager(
            $this->kmsClientProphecy->reveal(),
            $this->cacheProphecy->reveal(),
            self::TEST_KMS_CMK_ALIAS
        );

        $this->assertInstanceOf(KmsManager::class, $manager);
    }

    //-----------------------------------------------------------------------------------------------------------------
    // Test issues with using KMS

    /*
     * If KMS is unable to decrypt a key, KmsException will be thrown.
     * If the exception type is InvalidCiphertextException, we expect a KeyNotFoundException exception back.
     */
    public function testExceptionWhenInvalidCiphertextExceptionFromKms()
    {
        $this->expectException(KeyNotFoundException::class);

        //---

        $exceptionProphecy = $this->prophesize(KmsException::class);

        $exceptionProphecy->getAwsErrorCode()->willReturn('InvalidCiphertextException')->shouldBeCalled();

        $this->kmsClientProphecy->decrypt(Argument::any())->willThrow(
            $exceptionProphecy->reveal()
        );

        //---

        $manager = new KmsManager(
            $this->kmsClientProphecy->reveal(),
            $this->cacheProphecy->reveal(),
            self::TEST_KMS_CMK_ALIAS
        );

        $manager->getDecryptionKey('key-id');
    }

    /*
     * If KMS is unable to decrypt a key, KmsException will be thrown.
     * If the exception type is not InvalidCiphertextException, the exception will be re-throw.
     */
    public function testExceptionWhenOtherExceptionFromKms()
    {
        $this->expectException(KmsException::class);

        //---

        $exceptionProphecy = $this->prophesize(KmsException::class);
        $exceptionProphecy->getAwsErrorCode()->willReturn('OtherExceptionType')->shouldBeCalled();

        $this->kmsClientProphecy->decrypt(Argument::any())->willThrow(
            $exceptionProphecy->reveal()
        );

        //---

        $manager = new KmsManager(
            $this->kmsClientProphecy->reveal(),
            $this->cacheProphecy->reveal(),
            self::TEST_KMS_CMK_ALIAS
        );

        $manager->getDecryptionKey('key-id');
    }

    //-----------------------------------------------------------------------------------------------------------------
    // Test getting a decryption key

    /*
     * Test for when we need a decryption key that's already in the cache.
     */
    public function testGetDecryptionKeyWhenKeyIsInCache()
    {
        $keyCiphertext = 'test-key';
        $testMaterial = random_bytes(32);

        //---

        $this->cacheProphecy->get($keyCiphertext)->willReturn(new HiddenString($testMaterial));

        //---

        $manager = new KmsManager(
            $this->kmsClientProphecy->reveal(),
            $this->cacheProphecy->reveal(),
            self::TEST_KMS_CMK_ALIAS
        );

        $key = $manager->getDecryptionKey($keyCiphertext);

        $this->assertInstanceOf(Key::class, $key);
        $this->assertEquals($keyCiphertext, $key->getId());
        $this->assertEquals($testMaterial, $key->getKeyMaterial());
    }

    /*
     * Test that a key is returned when KMS is able to decrypted the passed Key Ciphertext.
     */
    public function testGetDecryptionKeyWhenKeyIsNotInCache()
    {
        $keyCiphertext = 'test-key';
        $keyCiphertextEncoded = base64_encode($keyCiphertext);
        $keyPlaintext = random_bytes(32);

        $testMaterial = new HiddenString(
            $keyPlaintext,
            true,
            false
        );

        //---

        // Cache will return false
        $this->cacheProphecy->get($keyCiphertextEncoded)->willReturn(false)->shouldBeCalled();

        // But we do expect the new key to be cached
        $this->cacheProphecy->store(
            $keyCiphertextEncoded,
            $testMaterial,
            KmsManager::DECRYPTION_KEY_TTL
        )->shouldBeCalled();

        //---

        $awsResultProphecy = $this->prophesize(AwsResult::class);

        $awsResultProphecy->get('CiphertextBlob')->willReturn($keyCiphertext);
        $awsResultProphecy->get('Plaintext')->willReturn($keyPlaintext);

        $this->kmsClientProphecy->decrypt([
            'CiphertextBlob' => $keyCiphertext,
        ])->willReturn($awsResultProphecy)->shouldBeCalled();

        //---

        $manager = new KmsManager(
            $this->kmsClientProphecy->reveal(),
            $this->cacheProphecy->reveal(),
            self::TEST_KMS_CMK_ALIAS
        );

        $key = $manager->getDecryptionKey($keyCiphertextEncoded);

        $this->assertInstanceOf(Key::class, $key);
        $this->assertEquals($keyCiphertextEncoded, $key->getId());
        $this->assertEquals($keyPlaintext, $key->getKeyMaterial());
    }

    //-----------------------------------------------------------------------------------------------------------------
    // Test getting the encryption key

    /*
     * Key getting the encryption key when one is stored in the cache
     */
    public function testGetEncryptionKeyWhenKeyIsInCache()
    {
        $keyCiphertext = 'test-key';
        $testMaterial = random_bytes(32);

        //---

        $this->cacheProphecy->get(KmsManager::CURRENT_ENCRYPTION_KEY)->willReturn([
            'id' => $keyCiphertext,
            'key_material' => new HiddenString($testMaterial),
        ]);

        //---

        $manager = new KmsManager(
            $this->kmsClientProphecy->reveal(),
            $this->cacheProphecy->reveal(),
            self::TEST_KMS_CMK_ALIAS
        );

        $key = $manager->getEncryptionKey();

        $this->assertInstanceOf(Key::class, $key);
        $this->assertEquals($keyCiphertext, $key->getId());
        $this->assertEquals($testMaterial, $key->getKeyMaterial());
    }


    public function testGetEncryptionKeyWhenKeyIsNotInCache()
    {
        $keyCiphertext = 'test-key';
        $keyCiphertextEncoded = base64_encode($keyCiphertext);
        $keyPlaintext = random_bytes(32);

        $testMaterial = new HiddenString(
            $keyPlaintext,
            true,
            false
        );

        //---

        // Cache will return false
        $this->cacheProphecy->get(KmsManager::CURRENT_ENCRYPTION_KEY)->willReturn(false)->shouldBeCalled();

        // But we do expect the new key to be cached for decryption
        $this->cacheProphecy->store(
            $keyCiphertextEncoded,
            $testMaterial,
            KmsManager::DECRYPTION_KEY_TTL
        )->shouldBeCalled();

        // And we'll also be storing the new encryption key in the cache
        $this->cacheProphecy->store(
            KmsManager::CURRENT_ENCRYPTION_KEY,
            [
                'id' => $keyCiphertextEncoded,
                'key_material' => $testMaterial,
            ],
            KmsManager::ENCRYPTION_KEY_TTL
        )->shouldBeCalled();

        //---

        $awsResultProphecy = $this->prophesize(AwsResult::class);

        $awsResultProphecy->get('CiphertextBlob')->willReturn($keyCiphertext);
        $awsResultProphecy->get('Plaintext')->willReturn($keyPlaintext);

        $this->kmsClientProphecy->generateDataKey([
            'KeyId' => self::TEST_KMS_CMK_ALIAS,
            'KeySpec' => 'AES_256',
        ])->willReturn($awsResultProphecy)->shouldBeCalled();

        //---

        $manager = new KmsManager(
            $this->kmsClientProphecy->reveal(),
            $this->cacheProphecy->reveal(),
            self::TEST_KMS_CMK_ALIAS
        );


        $key = $manager->getEncryptionKey();

        $this->assertInstanceOf(Key::class, $key);
        $this->assertEquals($keyCiphertextEncoded, $key->getId());
        $this->assertEquals($keyPlaintext, $key->getKeyMaterial());
    }
}
