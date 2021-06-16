<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Service\Lpa\FormatDate;
use DateTime;

/**
 * Class SortLpasTest
 *
 * @coversDefaultClass \Common\Service\Lpa\FormatDate
 * @package CommonTest\Service\Lpa
 */
class FormatDateTest extends LpaFixtureTestCase
{
    /**
     * @test
     * @covers ::__invoke
     */
    /**
     * @test
     * @dataProvider dateProvider
     */
    public function it_correctly_formats_date_for_letter($date, $locale, $expected)
    {
        $dateFormatter = new FormatDate();

        // retain the current locale
        $originalLocale = \Locale::getDefault();
        \Locale::setDefault($locale);

        $dateString = $dateFormatter($date);

        // restore the locale setting
        \Locale::setDefault($originalLocale);

        $this->assertEquals($expected, $dateString);
    }

    public function dateProvider()
    {
        return [
            [
                new DateTime('2021-06-01'),
                'en_GB',
                '1 June 2021',
            ],
            [
                new DateTime('today'),
                'en_GB',
                (new DateTime('now'))->format('j F Y')
            ],
        ];
    }
}
