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
     * @throws \Exception
     */
    public function isValid($reference_number)
    {
        $isValid = true;
        if (strlen($reference_number) === 12) {
            // Force the value to be a string so we can work with it like a string.
            $value = (string)$reference_number;

            // Set some initial values up.
            $length = strlen($value);
            $parity = $length % 2;
            $sum = 0;

            for ($i = $length - 1; $i >= 0; --$i) {
                // Extract a character from the value.
                $char = $value[$i];
                if ($i % 2 == $parity) {
                    $char *= 2;
                    if ($char > 9) {
                        $char -= 9;
                    }
                }
                // Add the character to the sum of characters.
                $sum += $char;
            }

            // Return the value of the sum multiplied by 9 and then modulus 10.
            if (($sum) % 10 != 0) {
                $this->error(self::INVALID_REFERENCE);
                $isValid = false;
            }
        }

        return $isValid;
    }
}
