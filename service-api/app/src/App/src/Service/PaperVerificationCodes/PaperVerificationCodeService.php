<?php

declare(strict_types=1);

namespace App\Service\PaperVerificationCodes;

use App\DataAccess\Repository\PaperVerificationCodesInterface;
use App\DataAccess\Repository\Response\PaperVerificationCode;
use App\Entity\LpaStore\LpaStore;
use App\Entity\Person;
use App\Enum\ActorStatus;
use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Enum\VerificationCodeExpiryReason;
use App\Enum\LpaType;
use App\Exception\ApiException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Request\PaperVerificationCodeUsable;
use App\Request\PaperVerificationCodeValidate;
use App\Request\PaperVerificationCodeView;
use App\Service\Log\EventCodes;
use App\Service\Lpa\LpaManagerInterface;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode as Code;
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

        $this->checkCodeUsable(
            $lpa,
            $params->code,
            $params->name,
            $verifiedCode->expiresAt,
            $verifiedCode->expiryReason
        );

        return new CodeUsable(
            donorName: ($lpa->donor->firstnames ?? '') . ' ' . ($lpa->donor->surname ?? ''),
            lpaType:   $lpa->caseSubtype ?? LpaType::PERSONAL_WELFARE,
            lpaStatus: LpaStatus::from($lpa->status ?? ''),
            lpaSource: LpaSource::LPASTORE,
            expiresAt: $verifiedCode->expiresAt,
            expiryReason: $verifiedCode->expiryReason,
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

        $this->checkCodeUsable(
            $lpa,
            $params->code,
            $params->name,
            $verifiedCode->expiresAt,
            $verifiedCode->expiryReason
        );

        $this->checkCodeValidates(
            $lpa,
            $params->code,
            $params->lpaUid,
            $params->sentToDonor,
            $params->attorneyName,
            $params->dateOfBirth,
            $params->noOfAttorneys
        );

        return new CodeValidate(
            donorName: ($lpa->donor->firstnames ?? '') . ' ' . ($lpa->donor->surname ?? ''),
            lpaType:   $lpa->caseSubtype ?? LpaType::PERSONAL_WELFARE,
            lpaStatus: LpaStatus::from($lpa->status ?? ''),
            lpaSource: LpaSource::LPASTORE,
            expiresAt: $verifiedCode->expiresAt,
        );
    }

    /**
     * @param PaperVerificationCodeView $params
     * @return CodeView
     * @throws ApiException
     * @throws GoneException
     * @throws NotFoundException
     */
    public function view(PaperVerificationCodeView $params): CodeView
    {
        $verifiedData = $this->paperVerificationCodes->validate($params->code);
        $verifiedCode = $verifiedData->getData();
        $lookupTime   = $verifiedData->getLookupTime();
        $lpa          = $this->getLpa($verifiedCode, (string) $params->code);

        $this->checkCodeUsable(
            $lpa,
            $params->code,
            $params->name,
            $verifiedCode->expiresAt,
            $verifiedCode->expiryReason
        );

        $this->checkCodeValidates(
            $lpa,
            $params->code,
            $params->lpaUid,
            $params->sentToDonor,
            $params->attorneyName,
            $params->dateOfBirth,
            $params->noOfAttorneys
        );

        if ($verifiedCode->expiryReason === null) {
            $_ = $this->paperVerificationCodes->expire(
                $params->code,
                VerificationCodeExpiryReason::FIRST_TIME_USE
            );

            $this->logger->notice(
                'First use of paper verification code, started expiry period',
                [
                    'event_code' => EventCodes::PAPER_VERIFICATION_CODE_FIRST_TIME_USE,
                ]
            );
        }

        $this->logger->notice(
            'Paper verification code organisation recorded',
            [
                'event_code'   => EventCodes::PAPER_VERIFICATION_CODE_ORGANISATION_VIEW,
                'code'         => $params->code,
                'lpa_uid'      => $params->lpaUid,
                'organisation' => $params->organisation,
                'lookup_time'  => $lookupTime,
            ]
        );

        return new CodeView(
            lpaSource: LpaSource::LPASTORE,
            lpa: $lpa,
        );
    }

    /**
     * @throws ApiException
     * @throws NotFoundException
     */
    private function getLpa(PaperVerificationCode $verifiedCode, string $originator): LpaStore
    {
        $lpa = $this->lpaManager->getByUid($verifiedCode->lpaUid, $originator);

        if ($lpa === null) {
            throw new NotFoundException(
                'LPA missing from upstream with verified paper verification code given',
                [
                    'event_code' => EventCodes::EXPECTED_LPA_MISSING,
                ]
            );
        }

        /** @var LpaStore */
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
        ?DateTimeInterface $expiresAt,
        ?VerificationCodeExpiryReason $expiryReason,
    ): void {
        // Does the donor match? If not then return nothing (Lpa not found with those details)
        if (
            $this->turnUnicodeCharToAscii(strtolower($lpa->donor->surname ?? ''))
            !==
            $this->turnUnicodeCharToAscii(strtolower($donorSurname))
        ) {
            $this->logger->info(
                'The donor name entered by the user to view the lpa with {code} does not match',
                ['code' => (string) $code]
            );
            throw new NotFoundException();
        }

        if ($expiresAt !== null && $this->clock->now() > $expiresAt) {
            $this->logger->info(
                'The paper verification code {code} entered by user to view LPA has expired',
                ['code' => (string) $code],
            );
            throw new GoneException(
                'Paper verification code expired',
                [
                    'reason' => $expiryReason?->value,
                ]
            );
        }
    }

    /**
     * @throws NotFoundException
     */
    private function checkCodeValidates(
        LpaStore $lpa,
        Code $code,
        LpaUid $lpaUid,
        bool $sentToDonor,
        string $attorneyName,
        DateTimeInterface $dateOfBirth,
        int $noOfAttorneys,
    ): void {
        if ((string) $lpaUid !== $lpa->uId) {
            $this->logger->info(
                'The the LpaUid entered by the user does not match the one found using the paper verification ' .
                'code {code}',
                ['code' => (string) $code]
            );
            throw new NotFoundException();
        }

        $attorney = $this->validateAttorneyInformation($lpa, $attorneyName);

        if ($attorney === null) {
            throw new NotFoundException();
        }

        $actorDateOfBirth = $sentToDonor ? $lpa->donor?->dob : $attorney->dob;
        // not exact comparison as we don't want to compare the objects, just the date data
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
        if ($actorDateOfBirth <> $dateOfBirth) {
            $this->logger->info(
                'The the date of birth entered by the user does not match the date of birth of the {actor}',
                [
                    'code'  => (string) $code,
                    'actor' => $sentToDonor ? 'attorney' : 'donor',
                ]
            );
            throw new NotFoundException();
        }

        if ($noOfAttorneys !== count($lpa->attorneys) + count($lpa->trustCorporations)) {
            $this->logger->info(
                'The the no. of attorneys entered by the user does not match the number found in the LPA',
                ['code' => (string) $code]
            );
            throw new NotFoundException();
        }
    }

    private function validateAttorneyInformation(
        LpaStore $lpa,
        string $attorneyName,
    ): ?Person {
        foreach ($lpa->attorneys as $attorney) {
            if ($attorney->getStatus() !== ActorStatus::ACTIVE) {
                continue;
            }

            $nameFromLpa    = $this->normaliseName(
                [
                    'first_names' => $attorney->firstnames,
                    'last_name'   => $attorney->surname,
                ]
            );
            $nameFromParams = $this->normaliseName($this->splitNameParts($attorneyName));
            if ($nameFromParams === $nameFromLpa) {
                return $attorney;
            }
        }

        $this->logger->info(
            'Did not find an attorney with the name {attorneyName}',
            [
                'attorneyName' => $attorneyName,
            ]
        );

        return null;
    }

    private function splitNameParts(string $name): array
    {
        $nameParts = explode(' ', $name);
        $lastName  = array_pop($nameParts);

        return [
            'first_names' => implode(' ', $nameParts),
            'last_name'   => $lastName,
        ];
    }

    /**
     * @param array{
     *     first_names: string,
     *     last_name: string,
     * } $name
     * @return string[]
     */
    private function normaliseName(array $name): array
    {
        $name['first_names'] = $this->turnUnicodeCharToAscii(strtolower($name['first_names']));
        $name['last_name']   = $this->turnUnicodeCharToAscii(strtolower($name['last_name']));

        return $name;
    }

    /**
     * Replace any unicode apostrophe's in string to an ascii [introduced to resolve iphone entry issue]
     *
     * @param string $string
     * @return string
     */
    private function turnUnicodeCharToAscii(string $string): string
    {
        $charsToReplace = ['’'];
        return str_ireplace($charsToReplace, '\'', $string);
    }
}
