<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\JavascriptVariablesExtension;
use Common\View\Twig\JavascriptVariablesExtensionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;

class JavascriptVariablesExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function testGetGlobals()
    {
        $analyticsId = 'uaid1234';
        $extension = new JavascriptVariablesExtension($analyticsId);

        $analaytics = $extension->getGlobals();

        $this->assertTrue(is_array($analaytics));
        $this->assertEquals(1,count($analaytics));

        $expectedAnalytics = [
                'uaId' => 'uaid1234',
        ];

        $this->assertEquals($expectedAnalytics, $analaytics);
        foreach ($analaytics as $analyticsId) {
            $this->assertEquals($expectedAnalytics['uaId'], $analyticsId);
        }
    }
}
