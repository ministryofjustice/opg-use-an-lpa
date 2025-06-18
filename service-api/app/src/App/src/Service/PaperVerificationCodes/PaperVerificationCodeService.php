<?php

declare(strict_types=1);

namespace App\Service\PaperVerificationCodes;

use App\Request\PaperVerificationCodeUsable;
use App\Request\PaperVerificationCodeValidate;
use App\Request\PaperVerificationCodeView;

class PaperVerificationCodeService
{
    /** @codeCoverageIgnore  */
    public function usable(PaperVerificationCodeUsable $params): void
    {
    }

    /** @codeCoverageIgnore  */
    public function validate(PaperVerificationCodeValidate $params): array
    {
        return [
            'name'          => $params->name,
            'code'          => $params->code,
            'lpaUid'        => $params->lpaUid,
            'sentToDonor'   => $params->sentToDonor,
            'attorneyName'  => $params->attorneyName,
            'donorName'     => 'Barbara Gilson',
            'dateOfBirth'   => $params->dateOfBirth,
            'noOfAttorneys' => $params->noOfAttorneys,
        ];
    }

    /** @codeCoverageIgnore  */
    public function view(PaperVerificationCodeView $params): void
    {
    }
}
