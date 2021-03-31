<?php

declare(strict_types=1);

namespace Common\Validator;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Regex;

class PasswordValidator extends AbstractValidator
{
    public const MUST_INCLUDE_DIGIT      = 'mustIncludeDigit';
    public const MUST_INCLUDE_LOWER_CASE = 'mustIncludeLowerCase';
    public const MUST_INCLUDE_UPPER_CASE = 'mustIncludeUpperCase';

    /**
     * @var string[]
     */
    protected $messageTemplates = [
        self::MUST_INCLUDE_DIGIT      => 'Password must include a number',
        self::MUST_INCLUDE_LOWER_CASE => 'Password must include a lower case letter',
        self::MUST_INCLUDE_UPPER_CASE => 'Password must include a capital letter',
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
