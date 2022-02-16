<?php

declare(strict_types=1);

namespace Common\Validator;

use Laminas\Validator\AbstractValidator;

/**
 * Class LuhnCheck
 *
 * @package Common\Validator
 */
class LuhnCheck extends AbstractValidator
{
    public const INVALID_REFERENCE = 'invalidReference';

    /**
     * @var string[]
     */
    protected $messageTemplates = [
        //  From parent
        self::INVALID_REFERENCE => 'The LPA reference number provided is not correct'
    ];

    /**
     * @param mixed $reference_number
     * @return bool
     */
    public function isValid($reference_number): bool
    {
        $isValid = true;

        /*
         * Check for reference length here because this validator is referred in both places ie,
         * newer journey where only sirius reference numbers will be entered by user [LpaAdd.php] and
         * older journey where both Sirius of Meris numbers could be entered [RequestReferenceNumber.php].
         * Luhn check only applies to Sirius reference
        */
        if (strlen($reference_number) === 12) {
            // Force the value to be a string so we can work with it like a string.
            $value = (string)$reference_number;

            // Set some initial values up.
            $length = strlen($value);
            $checkDigit  = $length % 2;
            $sum = 0;

            for ($i = $length - 1; $i >= 0; --$i) {
                // Extract a character from the value.
                $char = $value[$i];

                //Every other digit should be multiplied by two.
                if ($i % 2 === $checkDigit) {
                    $char *= 2;
                    //When the digit becomes 2 digits (due to digit*2),
                    //we add the two digits together.
                    if ($char > 9) {
                        $char -= 9;
                    }
                }
                // Add the character to the sum of characters.
                $sum += $char;
            }

            // Check if sum mod 10 equals zero
            if (($sum) % 10 !== 0) {
                $this->error(self::INVALID_REFERENCE);
                $isValid = false;
            }
        }

        return $isValid;
    }
}
