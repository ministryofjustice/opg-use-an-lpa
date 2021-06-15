<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use IntlDateFormatter;
use DateTime;

class FormatDate
{
    /**
     * Uses duplicated code from the LpaExtension class to ensure that the date we send out in the
     * letters if correctly localised.
     *
     * Violation of DRY so TODO: https://opgtransform.atlassian.net/browse/UML-1370
     *
     * @param \DateTimeInterface $date
     *
     * @return string
     */
    //private function localisedLetterExpectedDate(\DateTimeInterface $date): string
    public function __invoke(\DateTimeInterface $date): string
    {
        $formatter = IntlDateFormatter::create(
            \Locale::getDefault(),
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            'Europe/London',
            IntlDateFormatter::GREGORIAN
        );
        return $formatter->format($date);
    }
}
