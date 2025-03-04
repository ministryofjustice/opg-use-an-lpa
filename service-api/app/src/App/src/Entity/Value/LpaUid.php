<?php

declare(strict_types=1);

namespace App\Entity\Value;

use App\Enum\LpaSource;

/**
 * Value object to encapsulate an LPA identifier
 *
 * Allows identification of the source system that the LPA can be found in.
 */
class LpaUid
{
    public function __construct(private string $lpaUid)
    {
    }

    public function getLpaUid(): string
    {
        return $this->lpaUid;
    }

    public function getLpaSource(): LpaSource
    {
        return str_starts_with($this->lpaUid, 'M-')
            ? LpaSource::LPASTORE
            : LpaSource::SIRIUS;
    }

    public function __toString(): string
    {
        return $this->lpaUid;
    }
}
