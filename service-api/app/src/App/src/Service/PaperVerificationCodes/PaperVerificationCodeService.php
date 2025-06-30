<?php

declare(strict_types=1);

namespace App\Service\PaperVerificationCodes;

use App\Enum\LpaSource;
use App\Exception\NotFoundException;
use App\Request\PaperVerificationCodeUsable;
use App\Request\PaperVerificationCodeValidate;
use App\Request\PaperVerificationCodeView;
use App\Service\Lpa\IsValid\LpaStatus;
use App\Service\Lpa\LpaManagerInterface;
use DateInterval;
use DateTimeImmutable;

class PaperVerificationCodeService
{
    public function __construct(
        private readonly LpaManagerInterface $lpaManager,
    ) {
    }

    /** @codeCoverageIgnore  */
    public function usable(PaperVerificationCodeUsable $params): void
    {
    }

    /** @codeCoverageIgnore  */
    public function validate(PaperVerificationCodeValidate $params): CodeValidate
    {
        if ((string)$params->lpaUid === 'M-1111-2222-3333') {
            $codeData = [
                'Uid'     => 'M-789Q-P4DF-4UX3',
                'Expires' => (new DateTimeImmutable())->add(new DateInterval('P1Y')),
            ];
        } elseif ((string)$params->lpaUid === 'M-1234-1234-1234') {
            $codeData = [
                'Uid'       => 'M-789Q-P4DF-4UX3',
                'Expires'   => (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                'Cancelled' => 'yes',
            ];
        } else {
            throw new NotFoundException();
        }

        $lpa = $this->lpaManager->getByUid($codeData['Uid'], (string) $params->code);

        if ($lpa === null) {
            throw new NotFoundException();
        }

        $lpaObj = $lpa->getData();

        return new CodeValidate(
            donorName:      $lpaObj->getDonor()->getFirstnames() . ' ' . $lpaObj->getDonor()->getSurname(),
            lpaType:        $lpaObj->caseSubtype,
            codeExpiryDate: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
            lpaStatus:      LpaStatus::from($lpaObj->status),
            lpaSource:      LpaSource::LPASTORE,
        );
    }

    /** @codeCoverageIgnore  */
    public function view(PaperVerificationCodeView $params): void
    {
    }
}
