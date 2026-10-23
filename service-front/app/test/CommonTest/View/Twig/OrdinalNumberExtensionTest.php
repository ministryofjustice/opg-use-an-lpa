<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\OrdinalNumberExtension;
use InvalidArgumentException;
use Locale;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Twig\TwigFilter;

class OrdinalNumberExtensionTest extends TestCase
{
    public function testGetFilters(): void
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

    #[DataProvider('exceptionOrdinalDataProvider')]
    public function testMakeOrdinalException($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ordinals can only be provided for integers');

        $extension = new OrdinalNumberExtension();

        $extension->makeOrdinal($value);
    }

    public static function exceptionOrdinalDataProvider()
    {
        return [
            ['abc'],
            [null],
            [true],
            [false],
            [new stdClass()],
            [1.2345],
        ];
    }

    #[DataProvider('ordinalDataProvider')]
    public function testMakeOrdinal($locale, $value, $expected): void
    {
        $extension = new OrdinalNumberExtension();

        // retain the current locale
        $originalLocale = Locale::getDefault();
        Locale::setDefault($locale);

        $ordinal = $extension->makeOrdinal($value);

        // restore the locale setting
        Locale::setDefault($originalLocale);

        $this->assertEquals($expected, $ordinal);
    }

    public static function ordinalDataProvider()
    {
        return [
            [
                'en_GB',
                1,
                '1st',
            ],
            [
                'en_GB',
                2,
                '2nd',
            ],
            [
                'en_GB',
                3,
                '3rd',
            ],
            [
                'en_GB',
                4,
                '4th',
            ],
            [
                'en_GB',
                5,
                '5th',
            ],
            [
                'en_GB',
                6,
                '6th',
            ],
            [
                'en_GB',
                7,
                '7th',
            ],
            [
                'en_GB',
                8,
                '8th',
            ],
            [
                'en_GB',
                9,
                '9th',
            ],
            [
                'en_GB',
                10,
                '10th',
            ],
            [
                'en_GB',
                11,
                '11th',
            ],
            [
                'en_GB',
                12,
                '12th',
            ],
            [
                'en_GB',
                13,
                '13th',
            ],
            [
                'en_GB',
                14,
                '14th',
            ],
            [
                'en_GB',
                15,
                '15th',
            ],
            [
                'en_GB',
                16,
                '16th',
            ],
            [
                'en_GB',
                17,
                '17th',
            ],
            [
                'en_GB',
                18,
                '18th',
            ],
            [
                'en_GB',
                19,
                '19th',
            ],
            [
                'en_GB',
                20,
                '20th',
            ],
            [
                'en_GB',
                21,
                '21st',
            ],
            [
                'en_GB',
                22,
                '22nd',
            ],
            [
                'en_GB',
                23,
                '23rd',
            ],
            [
                'en_GB',
                24,
                '24th',
            ],
            [
                'en_GB',
                25,
                '25th',
            ],
            [
                'en_GB',
                26,
                '26th',
            ],
            [
                'en_GB',
                27,
                '27th',
            ],
            [
                'en_GB',
                28,
                '28th',
            ],
            [
                'en_GB',
                29,
                '29th',
            ],
            [
                'en_GB',
                30,
                '30th',
            ],
            [
                'en_GB',
                31,
                '31st',
            ],
            [
                'en_GB',
                32,
                '32nd',
            ],
            [
                'en_GB',
                33,
                '33rd',
            ],
            [
                'en_GB',
                34,
                '34th',
            ],
            [
                'en_GB',
                35,
                '35th',
            ],
            [
                'en_GB',
                36,
                '36th',
            ],
            [
                'en_GB',
                37,
                '37th',
            ],
            [
                'en_GB',
                38,
                '38th',
            ],
            [
                'en_GB',
                39,
                '39th',
            ],
            [
                'cy_GB',
                1,
                '1af',
            ],
            [
                'cy_GB',
                2,
                '2il',
            ],
            [
                'cy_GB',
                3,
                '3ydd',
            ],
            [
                'cy_GB',
                4,
                '4ydd',
            ],
            [
                'cy_GB',
                5,
                '5ed',
            ],
            [
                'cy_GB',
                6,
                '6ed',
            ],
            [
                'cy_GB',
                7,
                '7fed',
            ],
            [
                'cy_GB',
                8,
                '8fed',
            ],
            [
                'cy_GB',
                9,
                '9fed',
            ],
            [
                'cy_GB',
                10,
                '10fed',
            ],
            [
                'cy_GB',
                11,
                '11eg',
            ],
            [
                'cy_GB',
                12,
                '12fed',
            ],
            [
                'cy_GB',
                13,
                '13eg',
            ],
            [
                'cy_GB',
                14,
                '14eg',
            ],
            [
                'cy_GB',
                15,
                '15fed',
            ],
            [
                'cy_GB',
                16,
                '16eg',
            ],
            [
                'cy_GB',
                17,
                '17eg',
            ],
            [
                'cy_GB',
                18,
                '18fed',
            ],
            [
                'cy_GB',
                19,
                '19eg',
            ],
            [
                'cy_GB',
                20,
                '20fed',
            ],
            [
                'cy_GB',
                21,
                '21ain',
            ],
            [
                'cy_GB',
                22,
                '22ain',
            ],
            [
                'cy_GB',
                23,
                '23ain',
            ],
            [
                'cy_GB',
                24,
                '24ain',
            ],
            [
                'cy_GB',
                25,
                '25ain',
            ],
            [
                'cy_GB',
                26,
                '26ain',
            ],
            [
                'cy_GB',
                27,
                '27ain',
            ],
            [
                'cy_GB',
                28,
                '28ain',
            ],
            [
                'cy_GB',
                29,
                '29ain',
            ],
            [
                'cy_GB',
                30,
                '30ain',
            ],
            [
                'cy_GB',
                31,
                '31ain',
            ],
            [
                'cy_GB',
                32,
                '32ain',
            ],
            [
                'cy_GB',
                33,
                '33ain',
            ],
            [
                'cy_GB',
                34,
                '34ain',
            ],
            [
                'cy_GB',
                35,
                '35ain',
            ],
            [
                'cy_GB',
                36,
                '36ain',
            ],
            [
                'cy_GB',
                37,
                '37ain',
            ],
            [
                'cy_GB',
                38,
                '38ain',
            ],
            [
                'cy_GB',
                39,
                '39ain',
            ],
        ];
    }
}
