<?php

declare(strict_types=1);

namespace AppTest\Service\Session\KeyManager;

use App\Service\Session\KeyManager\Config;
use PHPUnit\Framework\TestCase;


class ConfigTest extends TestCase
{
    public function testValidName()
    {
        $name = 'test-name';

        $data = [
            'session' => [
                'key' => [
                    'name' => $name,
                ],
            ],
        ];

        $config = new Config($data);

        $this->assertEquals($name, $config->getName());
    }

    public function testMissingName()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('/name/');

        //---

        new Config([]);
    }
}
