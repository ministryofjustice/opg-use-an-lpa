<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Common\Service\Lpa\LocalisedDate;
use DateTime;
use Locale;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocalisedDate::class)]
class LocalisedDateTest extends TestCase
{
    #[DataProvider('dateProvider')]
    #[Test]
    public function it_correctly_formats_date_for_letter($date, $locale, $expected): void
    {
        $dateFormatter = new LocalisedDate();

        // retain the current locale
        $originalLocale = Locale::getDefault();
        Locale::setDefault($locale);

        $dateString = $dateFormatter($date);

        // restore the locale setting
        Locale::setDefault($originalLocale);

        $this->assertEquals($expected, $dateString);
    }

    public static function dateProvider()
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
                (new DateTime('now'))->format('j F Y'),
            ],
        ];
    }
}
