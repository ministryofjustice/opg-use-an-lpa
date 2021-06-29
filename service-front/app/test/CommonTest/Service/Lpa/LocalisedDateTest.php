<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Service\Lpa\LocalisedDate;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * Class LocalisedDateTest
 *
 * @coversDefaultClass \Common\Service\Lpa\LocalisedDate
 * @package CommonTest\Service\Lpa
 */
class LocalisedDateTest extends TestCase
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
        $dateFormatter = new LocalisedDate();

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
