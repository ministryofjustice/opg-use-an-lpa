<?php

declare(strict_types=1);

namespace Common\Validator;

use Laminas\Validator\EmailAddress as LaminasEmailAddressValidator;

class EmailAddressValidator extends LaminasEmailAddressValidator
{
    /**
     * Overridden function to translate error messages
     *
     * @param string $value
     * @return bool
     */
    public function isValid($value)
    {
        $valid = parent::isValid($value);

        if ($valid === false && count($this->getMessages()) > 0) {
            $this->abstractOptions['messages'] = [
                'Enter an email address in the correct format, like name@example.com'
            ];
        }

        return $valid;
    }
}
