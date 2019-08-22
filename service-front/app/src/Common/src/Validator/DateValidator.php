<?php

namespace Common\Validator;

use Zend\Validator\AbstractValidator;
use Zend\Validator\Regex;
use DateTime;

/**
 * Class DateValidator
 * @package Common\Validator
 */
class DateValidator extends AbstractValidator
{
    const DATE_INVALID_FORMAT = 'dateInvalidFormat';
    const DATE_EMPTY          = 'dateEmpty';
    const DATE_INCOMPLETE     = 'dateIncomplete';
    const DATE_INVALID        = 'dateInvalid';

    protected $messageTemplates = [
        self::DATE_INVALID_FORMAT => 'Date value must be provided in an array',
        self::DATE_EMPTY          => 'Enter a date',
        self::DATE_INCOMPLETE     => 'Date must include a day, month and year',
        self::DATE_INVALID        => 'Enter a real date',
    ];

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!is_array($value)
            || !array_key_exists('day', $value)
            || !array_key_exists('month', $value)
            || !array_key_exists('year', $value)) {

            $this->error(self::DATE_INVALID_FORMAT);

            return false;
        }

        if (empty($value['day']) && empty($value['month']) && empty($value['year'])) {
            $this->error(self::DATE_EMPTY);

            return false;
        }

        if (empty($value['day']) || empty($value['month']) || empty($value['year'])) {
            $this->error(self::DATE_INCOMPLETE);

            return false;
        }

        $parsedDate = $this->parseDateArray($value['day'], $value['month'], $value['year']);

        if (!$parsedDate instanceof DateTime) {
            $this->error(self::DATE_INVALID);

            return false;
        }

        return true;
    }

    /**
     * A parsed date will be returned if the value array represents a valid date value
     *
     * @param int $day
     * @param int $month
     * @param int $year
     * @return bool|DateTime|null
     */
    protected function parseDateArray($day, $month, $year)
    {
        if (is_numeric($day) && $day > 0
            && is_numeric($month) && $month > 0
            && is_numeric($year) && $year > 0) {

            //  Validate the individual values in isolation
            $dayValidator = new Regex('/\b(0?[1-9]|[12][0-9]|3[01])\b/');
            $monthValidator = new Regex('/\b(0?[1-9]|1[0-2])\b/');
            $yearValidator = new Regex('/\b([0-9]?[0-9]?[0-9]?[0-9])\b/');

            if ($dayValidator->isValid($day) && $monthValidator->isValid($month) && $yearValidator->isValid($year)) {
                //  Check that the values combined are a possible date
                $format = 'Y-n-j';
                $formattedDate = sprintf('%s-%s-%s', (int) $year, (int) $month, (int) $day);

                $date = DateTime::createFromFormat($format, $formattedDate);
                $derivedDate = $date->format($format);

                if ($formattedDate == $derivedDate) {
                    return $date;
                }
            }
        }

        return null;
    }
}
