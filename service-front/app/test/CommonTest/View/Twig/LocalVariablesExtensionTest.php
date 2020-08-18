<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\LanguageLocaleVariablesExtension;
use PHPUnit\Framework\TestCase;


class LocalVariablesExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function testGetGlobals()
    {
        $extension = new LanguageLocaleVariablesExtension();
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
