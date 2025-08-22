<?php

declare(strict_types=1);

namespace CommonTest\Service\Session\KeyManager;

use Common\Service\Session\KeyManager\Key;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Key::class)]
class KeyTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $testId       = '1';
        $testMaterial = hex2bin('0000000000000000000000000000000000000000000000000000000000000000');

        // We cannot mock `EncryptionKeyMaterial` as the class is marked as final. So use a real one.
        $material = new EncryptionKey(new HiddenString($testMaterial));

        $key = new Key($testId, $material);

        $this->assertEquals($testId, $key->getId());
        $this->assertEquals($testMaterial, $key->getKeyMaterial());
        $this->assertEquals($material, $key->getKey());
    }
}
