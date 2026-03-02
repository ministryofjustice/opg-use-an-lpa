<?php

declare(strict_types=1);

namespace Actor\Handler\ResolveLpaTypeTrait;

use Common\Service\Log\EventCodes;

trait LpaTypeResolver
{
    public function resolveLabel(string $subtype, string $lpaUid): string
    {
        $caseSubtype = strtolower($subtype);
        $lpaUid      = strtoupper($lpaUid);

        $isDigital = str_starts_with($lpaUid, 'M');

        if ($caseSubtype === 'hw') {
            return $isDigital
                ? $this->translator->translate('personal welfare')
                : $this->translator->translate('health and welfare');
        }

        return $isDigital
            ? $this->translator->translate('property and affairs')
            : $this->translator->translate('property and finance');
    }

    public function resolveEventCode(string $subtype): string
    {
        return strtolower($subtype) === 'hw'
            ? EventCodes::ADDED_LPA_TYPE_HW
            : EventCodes::ADDED_LPA_TYPE_PFA;
    }
}
