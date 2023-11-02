<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use DateTimeInterface;
use IntlDateFormatter;
use Locale;

class LocalisedDate
{
    /**
     * Ensure that the date we send out in the letters if correctly localised.
     *
     * @param  \DateTimeInterface $date
     * @return string
     */
    public function __invoke(DateTimeInterface $date): string
    {
        $formatter = IntlDateFormatter::create(
            Locale::getDefault(),
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            'Europe/London',
            IntlDateFormatter::GREGORIAN
        );
        return $formatter->format($date);
    }
}
