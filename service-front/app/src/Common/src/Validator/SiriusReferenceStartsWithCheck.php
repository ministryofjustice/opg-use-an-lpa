<?php

declare(strict_types=1);

namespace Common\Validator;

use Laminas\Validator\AbstractValidator;

class SiriusReferenceStartsWithCheck extends AbstractValidator
{
    public const string LPA_MUST_START_WITH = 'mustStartWithSeven';

    /**
     * @var string[]
     */
    protected array $messageTemplates = [
        self::LPA_MUST_START_WITH => 'LPA reference numbers that are 12 numbers long must begin with a 7',
    ];

    public function isValid($value): bool
    {
        $isValid = true;

        $positionOfSeven = stripos((string)$value, '7');

        if (!($positionOfSeven === 0) and strlen($value) === 12) {
            $this->error(self::LPA_MUST_START_WITH);
            $isValid = false;
        }

        return $isValid;
    }
}
