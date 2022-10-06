<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\JavascriptVariablesExtension;
use PHPUnit\Framework\TestCase;

class JavascriptVariablesExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function testGetGlobals()
    {
        $analyticsId = 'uaid1234';
        $extension   = new JavascriptVariablesExtension($analyticsId);

        $analaytics = $extension->getGlobals();

        $this->assertEquals(1, count($analaytics));

        $expectedAnalytics = [
                'uaId' => 'uaid1234',
        ];

        $this->assertEquals($expectedAnalytics, $analaytics);
        foreach ($analaytics as $analyticsId) {
            $this->assertEquals($expectedAnalytics['uaId'], $analyticsId);
        }
    }
}
