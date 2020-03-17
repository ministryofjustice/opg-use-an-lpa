<?php

namespace Common\Validator;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Regex;

class PasswordValidator extends AbstractValidator
{
    const MUST_INCLUDE_DIGIT      = 'mustIncludeDigit';
    const MUST_INCLUDE_LOWER_CASE = 'mustIncludeLowerCase';
    const MUST_INCLUDE_UPPER_CASE = 'mustIncludeUpperCase';

    protected $messageTemplates = [
        self::MUST_INCLUDE_DIGIT      => 'Your password must include at least one digit (0-9)',
        self::MUST_INCLUDE_LOWER_CASE => 'Your password must include at least one lower case letter (a-z)',
        self::MUST_INCLUDE_UPPER_CASE => 'Your password must include at least one capital letter (A-Z)',
    ];

    public function isValid($value)
    {
        $isValid = true;

        //  Check that a number has been provided
        $regExValidator = new Regex('/.*[0-9].*/');

        if (!$regExValidator->isValid($value)) {
            $this->error(self::MUST_INCLUDE_DIGIT);
            $isValid = false;
        }

        //  Check that a lower case letter has been provided
        $regExValidator = new Regex('/.*[a-z].*/');

        if (!$regExValidator->isValid($value)) {
            $this->error(self::MUST_INCLUDE_LOWER_CASE);
            $isValid = false;
        }

        //  Check that an upper case letter has been provided
        $regExValidator = new Regex('/.*[A-Z].*/');

        if (!$regExValidator->isValid($value)) {
            $this->error(self::MUST_INCLUDE_UPPER_CASE);
            $isValid = false;
        }

        return $isValid;
    }
}
