<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Lpa\Response\PaperVerificationCode;
use Common\Service\Log\EventCodes;
use Common\Service\Lpa\Response\Parse\ParsePaperVerificationCode;
use DateTimeInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Log\LoggerInterface;

class PaperVerificationCodeService
{
    public function __construct(
        private ApiClient $apiClient,
        private ParsePaperVerificationCode $parsePaperVerificationCode,
        private LoggerInterface $logger,
    ) {
    }

    public function usable(string $shareCode, string $donorSurname): PaperVerificationCodeResult
    {
        $this->logger->debug('User requested usable of LPA by paper verification code');

        try {
            $lpaData = $this->apiClient->httpPost('/v1/paper-verification/usable', [
                'code' => $shareCode,
                'name' => $donorSurname,
            ]);
        } catch (ApiException $apiEx) {
            switch ($apiEx->getCode()) {
                case StatusCodeInterface::STATUS_GONE:
                    if ($apiEx->getAdditionalData()['reason'] === 'cancelled') {
                        $this->logger->notice(
                            'Paper verification code {code} cancelled when attempting to fetch usable',
                            [
                                'event_code' => EventCodes::VIEW_LPA_PV_CODE_CANCELLED,
                                'code'       => $shareCode,
                            ]
                        );

                        return new PaperVerificationCodeResult(PaperVerificationCodeStatus::CANCELLED);
                    } else {
                        $this->logger->notice(
                            'Paper verification code {code} expired when attempting to fetch usable',
                            [
                                'event_code' => EventCodes::VIEW_LPA_PV_CODE_EXPIRED,
                                'code'       => $shareCode,
                            ]
                        );

                        return new PaperVerificationCodeResult(PaperVerificationCodeStatus::EXPIRED);
                    }

                case StatusCodeInterface::STATUS_NOT_FOUND:
                    $this->logger->notice(
                        'Paper verification code not found when attempting to fetch usable',
                        [
                            'event_code' => EventCodes::VIEW_LPA_PV_CODE_NOT_FOUND,
                        ]
                    );

                    return new PaperVerificationCodeResult(PaperVerificationCodeStatus::NOT_FOUND);
            }

            throw $apiEx;
        }

        $this->logger->notice(
            'LPA retrieved by paper verification code',
            [
                'event_code' => EventCodes::VIEW_LPA_PV_CODE_SUCCESS,
            ]
        );

        return new PaperVerificationCodeResult(
            PaperVerificationCodeStatus::OK,
            ($this->parsePaperVerificationCode)($lpaData),
        );
    }

    public function validate(
        string $shareCode,
        string $donorSurname,
        string $lpaReference,
        bool $sentToDonor,
        string $attorneyName,
        DateTimeInterface $dateOfBirth,
        int $noOfAttorneys,
    ): PaperVerificationCodeResult {
        $this->logger->debug('User requested validate of LPA by share code');

        try {
            $lpaData = $this->apiClient->httpPost('/v1/paper-verification/validate', [
                'code'          => $shareCode,
                'name'          => $donorSurname,
                'lpaUid'        => $lpaReference,
                'sentToDonor'   => $sentToDonor,
                'attorneyName'  => $attorneyName,
                'dateOfBirth'   => $dateOfBirth->format('Y-m-d'),
                'noOfAttorneys' => $noOfAttorneys,
            ]);
        } catch (ApiException $apiEx) {
            switch ($apiEx->getCode()) {
                case StatusCodeInterface::STATUS_GONE:
                    if ($apiEx->getAdditionalData()['reason'] === 'cancelled') {
                        $this->logger->notice(
                            'Paper verification code {code} cancelled when attempting to validate',
                            [
                                'event_code' => EventCodes::VIEW_LPA_PV_CODE_CANCELLED,
                                'code'       => $shareCode,
                            ]
                        );

                        return new PaperVerificationCodeResult(PaperVerificationCodeStatus::CANCELLED);
                    } else {
                        $this->logger->notice(
                            'Paper verification code {code} expired when attempting to validate',
                            [
                                'event_code' => EventCodes::VIEW_LPA_PV_CODE_EXPIRED,
                                'code'       => $shareCode,
                            ]
                        );

                        return new PaperVerificationCodeResult(PaperVerificationCodeStatus::EXPIRED);
                    }

                case StatusCodeInterface::STATUS_NOT_FOUND:
                    $this->logger->notice(
                        'Paper verification code not found when attempting to validate',
                        [
                            'event_code' => EventCodes::VIEW_LPA_PV_CODE_NOT_FOUND,
                        ]
                    );

                    return new PaperVerificationCodeResult(PaperVerificationCodeStatus::NOT_FOUND);
            }

            throw $apiEx;
        }

        return new PaperVerificationCodeResult(
            PaperVerificationCodeStatus::OK,
            ($this->parsePaperVerificationCode)($lpaData),
        );
    }
}
