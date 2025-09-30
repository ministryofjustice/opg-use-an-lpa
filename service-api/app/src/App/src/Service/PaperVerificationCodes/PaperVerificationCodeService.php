<?php

declare(strict_types=1);

namespace App\Service\PaperVerificationCodes;

use App\DataAccess\ApiGateway\PaperVerificationCodes;
use App\DataAccess\Repository\PaperVerificationCodesInterface;
use App\DataAccess\Repository\Response\PaperVerificationCode;
use App\Entity\LpaStore\LpaStore;
use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Enum\VerificationCodeExpiryReason;
use App\Exception\ApiException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Request\PaperVerificationCodeUsable;
use App\Request\PaperVerificationCodeValidate;
use App\Request\PaperVerificationCodeView;
use App\Service\Log\EventCodes;
use App\Service\Lpa\LpaManagerInterface;
use App\Value\PaperVerificationCode as Code;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

class PaperVerificationCodeService
{
    public function __construct(
        private readonly PaperVerificationCodesInterface $paperVerificationCodes,
        private readonly LpaManagerInterface $lpaManager,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws NotFoundException The verification code (or it's related LPA) have not been found
     * @throws GoneException The verification code has been cancelled or has expired
     * @throws ApiException
     */
    public function usable(PaperVerificationCodeUsable $params): CodeUsable
    {
        $verifiedCode = $this->paperVerificationCodes->validate($params->code)->getData();
        $lpa          = $this->getLpa($verifiedCode, (string) $params->code);

        $this->checkCodeUsable($lpa, $params->code, $params->name, $verifiedCode->cancelled, $verifiedCode->expiresAt);

        return new CodeUsable(
            donorName: $lpa->donor->firstnames . ' ' . $lpa->donor->surname,
            lpaType:   $lpa->caseSubtype,
            lpaStatus: LpaStatus::from($lpa->status),
            lpaSource: LpaSource::LPASTORE,
            expiresAt: $verifiedCode->expiresAt,
        );
    }

    /**
     * @throws NotFoundException The verification code (or it's related LPA) have not been found, or user supplied
     *                           information failed to validate against held data.
     * @throws GoneException     The verification code has been cancelled or has expired, or the Lpa has been cancelled
     * @throws ApiException
     */
    public function validate(PaperVerificationCodeValidate $params): CodeValidate
    {
        $verifiedCode = $this->paperVerificationCodes->validate($params->code)->getData();
        $lpa          = $this->getLpa($verifiedCode, (string) $params->code);

        $this->checkCodeUsable($lpa, $params->code, $params->name, $verifiedCode->cancelled, $verifiedCode->expiresAt);

        // TODO do all the checks now

        return new CodeValidate(
            donorName: $lpa->donor->firstnames . ' ' . $lpa->donor->surname,
            lpaType:   $lpa->caseSubtype,
            lpaStatus: LpaStatus::from($lpa->status),
            lpaSource: LpaSource::LPASTORE,
            expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
        );
    }

    /** @codeCoverageIgnore  */
    public function view(PaperVerificationCodeView $params): void
    {
        // this shows how this will work
        // $verifiedCode = $this->paperVerificationCodes->validate($params->code)->getData();

        // $this->expire($verifiedCode, VerificationCodeExpiryReason::FIRST_TIME_USE);
    }

    /**
     * @throws ApiException
     * @throws NotFoundException
     */
    public function expire(Code $codeToExpire, VerificationCodeExpiryReason $expiryReason): PaperVerificationCode
    {
        $expiredCode = $this->paperVerificationCodes->expire($codeToExpire, $expiryReason)->getData();
        return new PaperVerificationCode($expiredCode->lpaUid, false, $expiredCode->expiresAt, $expiryReason);
    }

    /**
     * @throws ApiException
     * @throws NotFoundException
     */
    private function getLpa(PaperVerificationCode $verifiedCode, string $originator): LpaStore
    {
        $lpa = $this->lpaManager->getByUid((string)$verifiedCode->lpaUid, $originator);

        if ($lpa === null) {
            throw new NotFoundException(
                'LPA missing from upstream with verified paper verification code given',
                [
                    'event_code' => EventCodes::EXPECTED_LPA_MISSING,
                ]
            );
        }

        return $lpa->getData();
    }

    /**
     * @throws NotFoundException
     * @throws GoneException
     */
    private function checkCodeUsable(
        LpaStore $lpa,
        Code $code,
        string $donorSurname,
        bool $cancelled,
        ?DateTimeInterface $expiresAt,
    ): void {
        // Does the donor match? If not then return nothing (Lpa not found with those details)
        if (
            strtolower($lpa->donor->surname ?? '') !== strtolower($donorSurname)
        ) {
            $this->logger->info(
                'The donor name entered by the user to view the lpa with {code} does not match',
                ['code' => (string) $code]
            );
            throw new NotFoundException();
        }

        if ($expiresAt !== null && $this->clock->now() > $expiresAt) {
            $this->logger->info(
                'The paper verification code {code} entered by user to view LPA has expired.',
                ['code' => (string) $code]
            );
            throw new GoneException('Paper verification code expired');
        }

        if ($cancelled) {
            $this->logger->info(
                'The paper verification code {code} entered by user is cancelled.',
                ['code' => (string) $code]
            );
            throw new GoneException('Paper verification code cancelled');
        }
    }
}
