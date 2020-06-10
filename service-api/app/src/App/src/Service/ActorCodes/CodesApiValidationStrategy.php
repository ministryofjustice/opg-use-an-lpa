<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

class CodesApiValidationStrategy implements CodeValidationStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function validateCode(string $code, string $uid, string $dob): string
    {
        // TODO: Implement validateCode() method.
    }

    /**
     * @inheritDoc
     */
    public function flagCodeAsUsed(string $code)
    {
        // TODO: Implement flagCodeAsUsed() method.
    }
}
