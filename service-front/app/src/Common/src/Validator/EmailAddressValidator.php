<?php

namespace Common\Validator;

use Zend\Validator\EmailAddress as ZfEmailAddressValidator;

class EmailAddressValidator extends ZfEmailAddressValidator
{
    /**
     * Overridden function to translate error messages
     *
     * @param string $value
     * @return bool
     */
    public function isValid($value)
    {
        var_dump("I am in Email Address Validator");
        var_dump($value);
        die;
        
        $valid = parent::isValid(trim($value));

        if ($valid === false && count($this->getMessages()) > 0) {
            $this->abstractOptions['messages'] = [
                'Enter a valid email address'
            ];
        }

        return $valid;
    }
}
