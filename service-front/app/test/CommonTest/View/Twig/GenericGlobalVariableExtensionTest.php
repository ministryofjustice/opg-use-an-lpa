<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\GenericGlobalVariableExtension;
use PHPUnit\Framework\TestCase;

class GenericGlobalVariableExtensionTest extends TestCase
{
    /** @test */
    public function sets_some_global_variables()
    {
        $application = 'actor';

        $extension     = new GenericGlobalVariableExtension($application);
        $genericConfig = $extension->getGlobals();
        $this->assertEquals(3, count($genericConfig));

        $expectedConfig = [
            'application'   => 'actor',
            'serviceName'   => 'Use a lasting power of attorney',
            'currentLocale' => 'cy-gb',
        ];

        $this->assertEquals($expectedConfig, $genericConfig);
        $this->assertEquals($expectedConfig['application'], $genericConfig['application']);
        $this->assertEquals($expectedConfig['serviceName'], $genericConfig['serviceName']);
        $this->assertEquals($expectedConfig['currentLocale'], $genericConfig['currentLocale']);
    }

    /** @test */
    public function sets_the_right_app_name()
    {
        $application = 'viewer';

        $extension     = new GenericGlobalVariableExtension($application);
        $genericConfig = $extension->getGlobals();

        $this->assertEquals('View a lasting power of attorney', $genericConfig['serviceName']);
    }
}
