<?php
declare(strict_types=1);

namespace ActorTest;

use Actor\ConfigProvider;

use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testCreatesAWorkingConfigProvider()
    {
        $cp = new ConfigProvider();

        $config = $cp();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('templates', $config);
    }
}