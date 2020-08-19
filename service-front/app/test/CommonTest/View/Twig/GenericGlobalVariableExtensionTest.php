<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\GenericGlobalVariableExtension;
use PHPUnit\Framework\TestCase;


class GenericGlobalVariableExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function testGetGlobals()
    {
        $application = 'actor';

        $extension = new GenericGlobalVariableExtension($application);
        $genericConfig = $extension->getGlobals();
        $this->assertTrue(is_array($genericConfig));
        $this->assertEquals(2, count($genericConfig));

        $expectedConfig = [
            'application' => 'actor',
            'currentLocale' => 'cy',
        ];

        $this->assertEquals($expectedConfig, $genericConfig);
        $this->assertEquals($expectedConfig['application'], $genericConfig['application']);
        $this->assertEquals($expectedConfig['currentLocale'], $genericConfig['currentLocale']);
    }
}
