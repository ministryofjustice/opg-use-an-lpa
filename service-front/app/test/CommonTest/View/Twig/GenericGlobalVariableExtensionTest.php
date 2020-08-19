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
        $extension = new GenericGlobalVariableExtension();
        $localLocale = $extension->getGlobals();
        $this->assertTrue(is_array($localLocale));
        $this->assertEquals(1, count($localLocale));

        $expectedLocale = [
            'currentLocale' => 'cy',
        ];

        $this->assertEquals($expectedLocale, $localLocale);
        foreach ($localLocale as $locale) {
            $this->assertEquals($expectedLocale['currentLocale'], $locale);
        }
    }
}
