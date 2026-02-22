<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Service\Log\EventCodes;

class LpaTypeResolver
{
    public function resolveLabel(string $subtype, string $lpaUid): string
    {
        $caseSubtype = strtolower($subtype);
        $reference   = strtoupper($lpaUid);

        $isDigital = str_starts_with($lpaUid, 'M');

        if ($caseSubtype === 'hw') {
            return $isDigital ? 'personal welfare' : 'health and welfare';
        }

        return $isDigital ? 'property and affairs' : 'property and finance';
    }

    public function resolveEventCode(string $subtype): string
    {
        return strtolower($subtype) === 'hw'
            ? EventCodes::ADDED_LPA_TYPE_HW
            : EventCodes::ADDED_LPA_TYPE_PFA;
    }
}
