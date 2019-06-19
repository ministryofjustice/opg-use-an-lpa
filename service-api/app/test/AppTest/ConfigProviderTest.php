<?php

declare(strict_types=1);

namespace AppTest;

use App\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testCreatesAWorkingConfigProvider()
    {
        $cp = new ConfigProvider();
        $config = $cp();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
    }
}