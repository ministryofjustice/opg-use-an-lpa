<?php

declare(strict_types=1);

namespace Common\Validator;

use Laminas\Validator\NotEmpty;
use Mezzio\Exception\RuntimeException;

class NotEmptyConditional extends NotEmpty
{
    public const DEPENDANT_KEY       = 'dependant';
    public const DEPENDANT_VALUE_KEY = 'dependant_value';

    private ?string $dependant      = null;
    private ?string $dependantValue = null;

    public function __construct($options = null)
    {
        if (array_key_exists(self::DEPENDANT_KEY, $options) && is_string($options[self::DEPENDANT_KEY])) {
            $this->dependant = $options[self::DEPENDANT_KEY];
        }
        if (array_key_exists(self::DEPENDANT_VALUE_KEY, $options) && is_string($options[self::DEPENDANT_VALUE_KEY])) {
            $this->dependantValue = $options[self::DEPENDANT_VALUE_KEY];
        }

        parent::__construct($options);
    }

    public function isValid($value, $context = null): bool
    {
        if ($this->dependant === null || $this->dependantValue === null) {
            throw new RuntimeException('conditional validator does not have conditions defined');
        }

        //if nothing is selected on dependant value we don't want to show an error
        if ($context[$this->dependant] === null) {
            return false;
        }

        return array_key_exists($this->dependant, $context) && $context[$this->dependant] === $this->dependantValue
            || parent::isValid($value);
    }
}
