<?php

declare(strict_types=1);

namespace App\Service\PaperVerificationCodes;

use App\DataAccess\Repository\ActorCodesInterface;
use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Exception\ApiException;
use App\Exception\GoneException;
use App\Exception\MissingCodeExpiryException;
use App\Exception\NotFoundException;
use App\Request\PaperVerificationCodeUsable;
use App\Request\PaperVerificationCodeValidate;
use App\Request\PaperVerificationCodeView;
use App\Service\Lpa\Combined\RejectInvalidLpa;
use App\Service\Lpa\LpaManagerInterface;
use DateInterval;
use DateTimeImmutable;

class PaperVerificationCodeService
{
    public function __construct(
        private readonly ActorCodesInterface $actorCodes,
        private readonly LpaManagerInterface $lpaManager,
        private readonly RejectInvalidLpa $rejectInvalidLpa,
    ) {
    }

    /**
     * @throws NotFoundException The verification code (or it's related LPA) have not been found
     * @throws GoneException The verification code has been cancelled or has expired
     * @throws ApiException
     */
    public function usable(PaperVerificationCodeUsable $params): CodeUsable
    {
        // 1. Fetch data from upstream paper verification code service (ActorCodes)
        //    a. If invalid throw PaperVerificationCode\NotFoundException
        //    b. If cancelled throw PaperVerificationCode\CancelledException is GoneException
        //    c. If expired throw PaperVerificationCode\ExpiredException is GoneException
        if ((string)$params->code === 'P-1234-1234-1234-12') {
            $codeData = [
                'Uid'     => 'M-789Q-P4DF-4UX3',
                'Expires' => (new DateTimeImmutable())->add(new DateInterval('P1Y')),
            ];
        } elseif ((string)$params->code === 'P-5678-5678-5678-56') {
            $codeData = [
                'Uid'     => 'M-789Q-P4DF-4UX3',
                'Expires' => (new DateTimeImmutable())->sub(new DateInterval('P1Y')),
            ];
        } elseif ((string)$params->code === 'P-3456-3456-3456-34') {
            $codeData = [
                'Uid'       => 'M-789Q-P4DF-4UX3',
                'Expires'   => (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                'Cancelled' => 'yes',
            ];
        } else {
            throw new NotFoundException();
        }
        // code above constitutes a mocked code service call

        $lpa = $this->lpaManager->getByUid($codeData['Uid'], (string) $params->code);

        if ($lpa === null) {
            throw new NotFoundException();
        }

        // Whilst the checks in this invokable could be done before we look up the LPA, they are done
        // at this point as we only want to acknowledge if a code has expired if the donor surname also matches
        try {
            ($this->rejectInvalidLpa)($lpa, (string) $params->code, $params->name, $codeData);
        } catch (MissingCodeExpiryException) {
            throw ApiException::create('Missing code expiry data in code service response');
        }

        $lpaObj = $lpa->getData();

        return new CodeUsable(
            donorName:      $lpaObj->getDonor()->getFirstnames() . ' ' . $lpaObj->getDonor()->getSurname(),
            lpaType:        $lpaObj->caseSubtype,
            codeExpiryDate: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
            lpaStatus:      LpaStatus::from($lpaObj->status),
            lpaSource:      LpaSource::LPASTORE,
        );
    }

    public function validate(PaperVerificationCodeValidate $params): CodeValidate
    {
        if ((string)$params->lpaUid === 'M-1111-2222-3333') {
            $codeData = [
                'Uid'     => 'M-789Q-P4DF-4UX3',
                'Expires' => (new DateTimeImmutable())->add(new DateInterval('P1Y')),
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
