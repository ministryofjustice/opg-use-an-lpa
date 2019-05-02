<?php

declare(strict_types=1);

namespace ViewerTest\Service\Session\KeyManager;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Aws\Kms\KmsClient;
use Aws\Result as AwsResult;
use Aws\Kms\Exception\KmsException;
use ParagonIE\HiddenString\HiddenString;
use Viewer\Service\Session\KeyManager\Key;
use Viewer\Service\Session\KeyManager\KeyCache;
use Viewer\Service\Session\KeyManager\Config;
use Viewer\Service\Session\KeyManager\KmsManager;
use Viewer\Service\Session\KeyManager\KeyNotFoundException;

class KmsManagerTest extends TestCase
{
    const TEST_KMS_CMK_ALIAS = 'test-alias-name';

    private $cacheProphercy;
    private $kmsClientProphercy;
    private $configProphercy;

    public function setUp()
    {
        // Constructor arguments
        $this->cacheProphercy = $this->prophesize(KeyCache::class);
        $this->kmsClientProphercy = $this->prophesize(KmsClient::class);
        $this->configProphercy = $this->prophesize(Config::class);

        // Config setup
        $this->configProphercy->getKeyAlias()->willReturn(self::TEST_KMS_CMK_ALIAS);
    }

    public function testCanInstantiate()
    {
        $manager = new KmsManager(
            $this->kmsClientProphercy->reveal(),
            $this->cacheProphercy->reveal(),
            $this->configProphercy->reveal()
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

        $exceptionProphercy = $this->prophesize(KmsException::class);

        $exceptionProphercy->getAwsErrorCode()->willReturn('InvalidCiphertextException')->shouldBeCalled();

        $this->kmsClientProphercy->decrypt(Argument::any())->willThrow(
            $exceptionProphercy->reveal()
        );

        //---

        $manager = new KmsManager(
            $this->kmsClientProphercy->reveal(),
            $this->cacheProphercy->reveal(),
            $this->configProphercy->reveal()
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

        $exceptionProphercy = $this->prophesize(KmsException::class);
        $exceptionProphercy->getAwsErrorCode()->willReturn('OtherExceptionType')->shouldBeCalled();

        $this->kmsClientProphercy->decrypt(Argument::any())->willThrow(
            $exceptionProphercy->reveal()
        );

        //---

        $manager = new KmsManager(
            $this->kmsClientProphercy->reveal(),
            $this->cacheProphercy->reveal(),
            $this->configProphercy->reveal()
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

        $this->cacheProphercy->get($keyCiphertext)->willReturn(new HiddenString($testMaterial));

        //---

        $manager = new KmsManager(
            $this->kmsClientProphercy->reveal(),
            $this->cacheProphercy->reveal(),
            $this->configProphercy->reveal()
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
        $this->cacheProphercy->get($keyCiphertextEncoded)->willReturn(false)->shouldBeCalled();

        // But we do expect the new key to be cached
        $this->cacheProphercy->store(
            $keyCiphertextEncoded,
            $testMaterial,
            KmsManager::DECRYPTION_KEY_TTL
        )->shouldBeCalled();

        //---

        $awsResultProphercy = $this->prophesize(AwsResult::class);

        $awsResultProphercy->get('CiphertextBlob')->willReturn($keyCiphertext);
        $awsResultProphercy->get('Plaintext')->willReturn($keyPlaintext);

        $this->kmsClientProphercy->decrypt([
            'CiphertextBlob' => $keyCiphertext,
        ])->willReturn($awsResultProphercy)->shouldBeCalled();

        //---

        $manager = new KmsManager(
            $this->kmsClientProphercy->reveal(),
            $this->cacheProphercy->reveal(),
            $this->configProphercy->reveal()
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

        $this->cacheProphercy->get(KmsManager::CURRENT_ENCRYPTION_KEY)->willReturn([
            'id' => $keyCiphertext,
            'key_material' => new HiddenString($testMaterial),
        ]);

        //---

        $manager = new KmsManager(
            $this->kmsClientProphercy->reveal(),
            $this->cacheProphercy->reveal(),
            $this->configProphercy->reveal()
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
        $this->cacheProphercy->get(KmsManager::CURRENT_ENCRYPTION_KEY)->willReturn(false)->shouldBeCalled();

        // But we do expect the new key to be cached for decryption
        $this->cacheProphercy->store(
            $keyCiphertextEncoded,
            $testMaterial,
            KmsManager::DECRYPTION_KEY_TTL
        )->shouldBeCalled();

        // And we'll also be storing the new encryption key in the cache
        $this->cacheProphercy->store(
            KmsManager::CURRENT_ENCRYPTION_KEY,
            [
                'id' => $keyCiphertextEncoded,
                'key_material' => $testMaterial,
            ],
            KmsManager::ENCRYPTION_KEY_TTL
        )->shouldBeCalled();

        //---

        $awsResultProphercy = $this->prophesize(AwsResult::class);

        $awsResultProphercy->get('CiphertextBlob')->willReturn($keyCiphertext);
        $awsResultProphercy->get('Plaintext')->willReturn($keyPlaintext);

        $this->kmsClientProphercy->generateDataKey([
            'KeyId' => self::TEST_KMS_CMK_ALIAS,
            'KeySpec' => 'AES_256',
        ])->willReturn($awsResultProphercy)->shouldBeCalled();

        //---

        $manager = new KmsManager(
            $this->kmsClientProphercy->reveal(),
            $this->cacheProphercy->reveal(),
            $this->configProphercy->reveal()
        );


        $key = $manager->getEncryptionKey();

        $this->assertInstanceOf(Key::class, $key);
        $this->assertEquals($keyCiphertextEncoded, $key->getId());
        $this->assertEquals($keyPlaintext, $key->getKeyMaterial());
    }
}
