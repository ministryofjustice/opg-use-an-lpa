<?php

declare(strict_types=1);

namespace AppTest\Service\Session\KeyManager;

use App\Service\Session\KeyManager\Key;
use ParagonIE\Halite\Symmetric\EncryptionKey as EncryptionKeyMaterial;
use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;


class KeyTest extends TestCase
{
    public function testGetters()
    {
        $testId = 1;
        $testMaterial = hex2bin('0000000000000000000000000000000000000000000000000000000000000000');

        // We cannot mock `EncryptionKeyMaterial` as the class is marked as final. So use a real one.
        $material = new EncryptionKeyMaterial(new HiddenString($testMaterial));

        $key = new Key($testId, $material);

        $this->assertEquals($testId, $key->getId());
        $this->assertEquals($testMaterial, $key->getKeyMaterial());
    }

    /*
     * Key must be exactly 256 bits.
     */
    public function testInvalidShortLengthKey()
    {
        $this->expectException(InvalidKey::class);

        //---

        $testId = 1;
        $testMaterial = hex2bin('00');  // Too short

        // We cannot mock `EncryptionKeyMaterial` as the class is marked as final. So use a real one.
        $material = new EncryptionKeyMaterial(new HiddenString($testMaterial));

        new Key($testId, $material);
    }

    /*
     * Key must be exactly 256 bits.
     */
    public function testInvalidLongLengthKey()
    {
        $this->expectException(InvalidKey::class);

        //---

        $testId = 1;
        $testMaterial = hex2bin('000000000000000000000000000000000000000000000000000000000000000000');  // Too long

        // We cannot mock `EncryptionKeyMaterial` as the class is marked as final. So use a real one.
        $material = new EncryptionKeyMaterial(new HiddenString($testMaterial));

        new Key($testId, $material);
    }
}
