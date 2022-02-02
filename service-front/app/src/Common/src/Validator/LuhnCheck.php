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
    public const LPA_MUST_START_WITH = 'mustStartWithSeven';

    /**
     * @var string[]
     */
    protected $messageTemplates = [
        //  From parent
        self::INVALID_REFERENCE => 'The LPA reference number provided is not correct',
        self::LPA_MUST_START_WITH  => 'LPA reference numbers that are 12 numbers long must begin with a 7',
    ];

    /**
     * @param mixed $reference_number
     * @return bool
     * @throws \Exception
     */
    public function isValid($reference_number)
    {
        $isValid = true;
        $ref_number_checksum = '';

        $positionOfSeven = stripos((string)$reference_number, '7');

        if (strlen($reference_number) === 12) {
            if (!($positionOfSeven === 0)) {
                $this->error(self::LPA_MUST_START_WITH);
                $isValid = false;
            } else {
                foreach (str_split(strrev((string)$reference_number)) as $i => $d) {
                    $ref_number_checksum .= $i % 2 !== 0 ? $d * 2 : $d;
                }

                if (array_sum(str_split($ref_number_checksum)) % 10 != 0) {
                    $this->error(self::INVALID_REFERENCE);
                    $isValid = false;
                }
            }
        }
        return $isValid;
    }
}
