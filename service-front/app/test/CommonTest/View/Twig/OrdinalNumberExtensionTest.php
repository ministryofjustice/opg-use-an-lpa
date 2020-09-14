<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\OrdinalNumberExtension;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class OrdinalNumberExtensionTest extends TestCase
{
    public function testGetFilters()
    {
        $extension = new OrdinalNumberExtension();

        $filters = $extension->getFilters();

        $this->assertTrue(is_array($filters));
        $this->assertEquals(1, count($filters));

        $expectedFilters = [
            'ordinal' => 'makeOrdinal',
        ];

        //  Check each filter
        foreach ($filters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            /** @var TwigFilter $filter */
            $this->assertContains($filter->getName(), array_keys($expectedFilters));

            $filterCallable = $filter->getCallable();
            $this->assertInstanceOf(OrdinalNumberExtension::class, $filterCallable[0]);
            $this->assertEquals($expectedFilters[$filter->getName()], $filterCallable[1]);
        }
    }

    /**
     * @dataProvider exceptionOrdinalDataProvider
     */
    public function testMakeOrdinalException($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ordinals can only be provided for integers');

        $extension = new OrdinalNumberExtension();

        $extension->makeOrdinal($value);
    }

    public function exceptionOrdinalDataProvider()
    {
        return [
            [
                'abc',
                null,
                true,
                false,
                new \stdClass(),
                1.2345
            ],
        ];
    }

    /**
     * @dataProvider ordinalDataProvider
     */
    public function testMakeOrdinal($locale, $value, $expected)
    {
        $extension = new OrdinalNumberExtension();

        // retain the current locale
        $originalLocale = \Locale::getDefault();
        \Locale::setDefault($locale);

        $ordinal = $extension->makeOrdinal($value);

        // restore the locale setting
        \Locale::setDefault($originalLocale);

        $this->assertEquals($expected, $ordinal);
    }

    public function ordinalDataProvider()
    {
        return [
            ['en', 1, '1st'],
            ['en', 2, '2nd'],
            ['en', 3, '3rd'],
            ['en', 4, '4th'],
            ['en', 5, '5th'],
            ['en', 6, '6th'],
            ['en', 7, '7th'],
            ['en', 8, '8th'],
            ['en', 9, '9th'],
            ['en', 10, '10th'],
            ['en', 11, '11th'],
            ['en', 12, '12th'],
            ['en', 13, '13th'],
            ['en', 14, '14th'],
            ['en', 15, '15th'],
            ['en', 16, '16th'],
            ['en', 17, '17th'],
            ['en', 18, '18th'],
            ['en', 19, '19th'],
            ['en', 20, '20th'],
            ['en', 21, '21st'],
            ['en', 22, '22nd'],
            ['en', 23, '23rd'],
            ['en', 24, '24th'],
            ['en', 25, '25th'],
            ['en', 26, '26th'],
            ['en', 27, '27th'],
            ['en', 28, '28th'],
            ['en', 29, '29th'],
            ['en', 30, '30th'],
            ['en', 31, '31st'],
            ['en', 32, '32nd'],
            ['en', 33, '33rd'],
            ['en', 34, '34th'],
            ['en', 35, '35th'],
            ['en', 36, '36th'],
            ['en', 37, '37th'],
            ['en', 38, '38th'],
            ['en', 39, '39th'],
            ['cy', 1, '1af'],
            ['cy', 2, '2il'],
            ['cy', 3, '3ydd'],
            ['cy', 4, '4ydd'],
            ['cy', 5, '5ed'],
            ['cy', 6, '6ed'],
            ['cy', 7, '7fed'],
            ['cy', 8, '8fed'],
            ['cy', 9, '9fed'],
            ['cy', 10, '10fed'],
            ['cy', 11, '11eg'],
            ['cy', 12, '12fed'],
            ['cy', 13, '13eg'],
            ['cy', 14, '14eg'],
            ['cy', 15, '15fed'],
            ['cy', 16, '16eg'],
            ['cy', 17, '17eg'],
            ['cy', 18, '18fed'],
            ['cy', 19, '19eg'],
            ['cy', 20, '20fed'],
            ['cy', 21, '21ain'],
            ['cy', 22, '22ain'],
            ['cy', 23, '23ain'],
            ['cy', 24, '24ain'],
            ['cy', 25, '25ain'],
            ['cy', 26, '26ain'],
            ['cy', 27, '27ain'],
            ['cy', 28, '28ain'],
            ['cy', 29, '29ain'],
            ['cy', 30, '30ain'],
            ['cy', 31, '31ain'],
            ['cy', 32, '32ain'],
            ['cy', 33, '33ain'],
            ['cy', 34, '34ain'],
            ['cy', 35, '35ain'],
            ['cy', 36, '36ain'],
            ['cy', 37, '37ain'],
            ['cy', 38, '38ain'],
            ['cy', 39, '39ain']
        ];
    }
}
