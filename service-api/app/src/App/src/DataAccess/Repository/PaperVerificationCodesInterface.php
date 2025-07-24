<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\Repository\Response\PaperVerificationCode;
use App\DataAccess\Repository\Response\ResponseInterface;
use App\Value\PaperVerificationCode as Code;
use DateTimeInterface;

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
     * @psalm-return ResponseInterface<DateTimeInterface>
     */
    public function startExpiry(Code $code): ResponseInterface;
}
