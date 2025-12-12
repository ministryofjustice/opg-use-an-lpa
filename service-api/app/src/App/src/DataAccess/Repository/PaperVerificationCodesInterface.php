<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\Repository\Response\PaperVerificationCode;
use App\DataAccess\Repository\Response\PaperVerificationCodeExpiry;
use App\DataAccess\Repository\Response\ResponseInterface;
use App\Enum\VerificationCodeExpiryReason;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode as Code;

interface PaperVerificationCodesInterface
{
    /**
     * Validate that the code exists and is usable.
     *
     * @psalm-return ResponseInterface<PaperVerificationCode>
     */
    public function validate(Code $code): ResponseInterface;

    /**
     * Begins the expiry timer on the provided code (if it is not already in-progress)
     *
     * @psalm-return ResponseInterface<PaperVerificationCodeExpiry>
     */
    public function expire(Code $code, VerificationCodeExpiryReason $reason): ResponseInterface;

    /**
     * Asks the codes service to transition the paper channel actor to digital by
     * expiring their active paper verification codes
     *
     * @return ResponseInterface<PaperVerificationCodeExpiry>
     */
    public function transitionToDigital(LpaUid $lpaUid, string $actorUid): ResponseInterface;
}
