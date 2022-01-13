<?php

declare(strict_types=1);

namespace Common\Validator;

use Laminas\Validator\AbstractValidator;

class ChecksumValidator extends AbstractValidator
{
    public const MERIS_NO_MUST_START_WITH = 'mustStartWith';
    public const MUST_BE_LENGTH = 'mustBeLength';
    public const LPA_MUST_START_WITH = 'mustStartWithSeven';
    public const NOT_VALID = 'notValid';

    /**
     * @var string[]
     */
    protected $messageTemplates = [
        self::MERIS_NO_MUST_START_WITH => 'LPA reference numbers that are 7 numbers long must begin with a 2 or 3',
        self::LPA_MUST_START_WITH  => 'LPA reference numbers that are 12 numbers long must begin with a 7',
        self::MUST_BE_LENGTH => 'Enter an LPA reference number that is either 7 or 12 numbers long',
        self::NOT_VALID => 'Entered LPA reference number is not correct',
    ];

    /**
     * @param string $value
     *
     * @return bool
     */
    public function isValid($reference_number)
    {
        $isValid = true;
        $ref_number_checksum = '';

        //check flag 'allow_meris_lpas' status here
        if (!(strlen($reference_number) == 7 or strlen($reference_number) == 12)) {
            $this->error(self::MUST_BE_LENGTH);
            $isValid = false;
        } elseif (strlen($reference_number) == 12 and !preg_match('/^7/', (string)$reference_number)) {
            $this->error(self::LPA_MUST_START_WITH);
            $isValid = false;
        } elseif (
            strlen($reference_number) == 7 and
            !(preg_match('/^2/', (string)$reference_number) or
                preg_match('/^3/', (string)$reference_number))
        ) {
            $this->error(self::MERIS_NO_MUST_START_WITH);
            $isValid = false;
        } else {
            foreach (str_split(strrev((string)$reference_number)) as $i => $d) {
                $ref_number_checksum .= $i % 2 !== 0 ? $d * 2 : $d;
            }

            if (array_sum(str_split($ref_number_checksum)) % 10 != 0) {
                $this->error(self::NOT_VALID);
                $isValid = false;
            }
        }

        return $isValid;
    }
}
