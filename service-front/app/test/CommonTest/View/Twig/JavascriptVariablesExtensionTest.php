<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\Service\Security\CSPNonce;
use Common\View\Twig\JavascriptVariablesExtension;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Common\View\Twig\JavascriptVariablesExtension
 */
class JavascriptVariablesExtensionTest extends TestCase
{
    /**
     * @test
     * @covers ::__construct
     * @covers ::getGlobals
     */
    public function testGetGlobals(): void
    {
        $analyticsId = 'uaid1234';
        $nonce       = new CSPNonce('test');
        $extension   = new JavascriptVariablesExtension($nonce, $analyticsId);

        $analytics = $extension->getGlobals();

        $this->assertEquals(2, count($analytics));

        $expectedAnalytics = [
            'cspNonce' => $nonce,
            'uaId'     => 'uaid1234',
        ];

        $this->assertEquals($expectedAnalytics, $analytics);
    }
}
