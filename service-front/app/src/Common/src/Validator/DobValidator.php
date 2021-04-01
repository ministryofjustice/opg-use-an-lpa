<?php

declare(strict_types=1);

namespace Common\Validator;

use DateTime;

/**
 * Class DobValidator
 * @package Common\Validator
 */
class DobValidator extends DateValidator
{
    public const AGE_NEGATIVE = 'ageNegative';
    public const AGE_TOO_YOUNG = 'ageTooYoung';

    /**
     * @var string[]
     */
    protected $messageTemplates = [
        //  From parent
        self::DATE_INVALID_FORMAT => 'Date of birth value must be provided in an array',
        self::DATE_EMPTY          => 'Enter your date of birth',
        self::DATE_INVALID        => 'Date of birth must be a real date',
        self::DAY_INCOMPLETE      => 'Date of birth must include a day',
        self::MONTH_INCOMPLETE    => 'Date of birth must include a month',
        self::YEAR_INCOMPLETE     => 'Date of birth must include a year',

        self::AGE_NEGATIVE        => 'Date of birth must be in the past',
        self::AGE_TOO_YOUNG       => 'Check your date of birth is correct - you cannot be an attorney or donor if you’re under 18',
        ];

    /**
     * @param mixed $value
     * @return bool
     * @throws \Exception
     */
    public function isValid($value)
    {
        $valid = parent::isValid($value);

        if ($valid) {
            //  This can't fail if the parent validation has run successfully
            $date = $this->parseDateArray($value['day'], $value['month'], $value['year']);
            $now = new DateTime();

            if ($date > $now) {
                $this->error(self::AGE_NEGATIVE);

                $valid = false;
            } else {
                //  Validate the age is 18
                $then = $now->modify('-18 years');

                if ($date > $then) {
                    $this->error(self::AGE_TOO_YOUNG);

                    $valid = false;
                }
            }
        }

        return $valid;
    }
}
