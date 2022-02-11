<?php

declare(strict_types=1);

namespace Common\Validator;

use Laminas\Validator\AbstractValidator;

class MerisReferenceCheckValidator extends AbstractValidator
{
    public const MERIS_NO_MUST_START_WITH = 'mustStartWith';
    public const MUST_BE_LENGTH = 'mustBeLength';

    /**
     * @var string[]
     */
    protected $messageTemplates = [
        self::MERIS_NO_MUST_START_WITH => 'LPA reference numbers that are 7 numbers long must begin with a 2 or 3',
        self::MUST_BE_LENGTH => 'Enter an LPA reference number that is either 7 or 12 numbers long',
    ];

    /**
     * @param string $reference_number
     *
     * @return bool
     */
    public function isValid($reference_number): bool
    {
        $isValid = true;

        $positionOfTwo = stripos((string)$reference_number, '2');
        $positionOfThree = stripos((string)$reference_number, '3');

        if (!(strlen($reference_number) === 7 or strlen($reference_number) === 12)) {
            $this->error(self::MUST_BE_LENGTH);
            $isValid = false;
        }

        if (!($positionOfTwo === 0 or $positionOfThree === 0) and strlen($reference_number) === 7) {
            $this->error(self::MERIS_NO_MUST_START_WITH);
            $isValid = false;
        }

        return $isValid;
    }
}
