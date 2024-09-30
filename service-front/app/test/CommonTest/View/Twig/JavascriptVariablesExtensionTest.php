<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Common\Service\Security\CSPNonce;
use Common\View\Twig\JavascriptVariablesExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(JavascriptVariablesExtension::class)]
class JavascriptVariablesExtensionTest extends TestCase
{
    #[Test]
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
