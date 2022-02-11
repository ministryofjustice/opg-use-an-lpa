<?php

declare(strict_types=1);

namespace Common\Validator;

use Laminas\Validator\AbstractValidator;

class SiriusReferenceStartsWithCheck extends AbstractValidator
{
    public const LPA_MUST_START_WITH = 'mustStartWithSeven';

    /**
     * @var string[]
     */
    protected $messageTemplates = [
        self::LPA_MUST_START_WITH  => 'LPA reference numbers that are 12 numbers long must begin with a 7'
    ];

    /**
     * @param string $reference_number
     *
     * @return bool
     */
    public function isValid($reference_number): bool
    {
        $isValid = true;

        $positionOfSeven = stripos((string)$reference_number, '7');

        if (!(strlen($reference_number) === 12 and $positionOfSeven === 0)) {
            $this->error(self::LPA_MUST_START_WITH);
            $isValid = false;
        }

        return $isValid;
    }
}
