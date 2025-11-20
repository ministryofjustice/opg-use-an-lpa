<?php

declare(strict_types=1);

namespace App\Value;

use App\Enum\LpaSource;

/**
 * Value object to encapsulate an LPA identifier
 *
 * Allows identification of the source system that the LPA can be found in.
 */
class LpaUid
{
    private string $lpaUid;

    public function __construct(string $value)
    {
        if ($value[0] === 'M' && strlen($value) === 13) {
            $this->lpaUid = 'M-' . substr($value, 1, 4) . '-' . substr($value, 5, 4) . '-' . substr($value, 9, 4);
        } else {
            $this->lpaUid = $value;
        }
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
