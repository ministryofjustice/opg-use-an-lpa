<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Common\Service\Lpa\Response\AccessForAllResult;
use Common\Service\Lpa\Response\Parse\ParseLpaMatch;
use Common\Service\Lpa\Response\Parse\ParseActivationKeyExists;
use Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAdded;
use DateTimeInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Single action invokeable class that is responsible for calling the APIs necessary to add older
 * LPAs to a users account.
 */
class AddAccessForAllLpa
{
    // Exception messages returned from the API layer
    private const LPA_NOT_ELIGIBLE          = 'LPA not eligible due to registration date';
    private const LPA_DOES_NOT_MATCH        = 'LPA details do not match';
    private const LPA_HAS_ACTIVATION_KEY    = 'LPA has an activation key already';
    private const LPA_ALREADY_ADDED         = 'LPA already added';
    private const LPA_NEEDS_CLEANSING       = 'LPA needs cleansing';
    private const LPA_KEY_ALREADY_REQUESTED = 'Activation key already requested for LPA';
    private const LPA_POSTCODE_NOT_SUPPLIED = 'Postcode not supplied';
    private const LPA_STATE_INVALID         = 'LPA status invalid';

    /**
     * @param ApiClient                $apiClient
     * @param LoggerInterface          $logger
     * @param ParseLpaAlreadyAdded     $parseLpaAlreadyAddedResponse
     * @param ParseActivationKeyExists $parseActivationKeyExistsResponse
     * @param ParseLpaMatch            $parseAccessForAllLpaMatchResponse
     * @codeCoverageIgnore
     */
    public function __construct(
        private ApiClient $apiClient,
        private LoggerInterface $logger,
        private ParseLpaAlreadyAdded $parseLpaAlreadyAddedResponse,
        private ParseActivationKeyExists $parseActivationKeyExistsResponse,
        private ParseLpaMatch $parseAccessForAllLpaMatchResponse,
    ) {
    }

    public function validate(
        string $userToken,
        int $lpaUid,
        string $firstnames,
        string $lastname,
        DateTimeInterface $dob,
        string $postcode,
    ): AccessForAllApiResult {
        $data = [
            'reference_number'     => $lpaUid,
            'first_names'          => $firstnames,
            'last_name'            => $lastname,
            'dob'                  => $dob->format('Y-m-d'),
            'postcode'             => $postcode,
            'force_activation_key' => false,
        ];

        $this->apiClient->setUserTokenHeader($userToken);

        try {
            $response = $this->apiClient->httpPost('/v1/older-lpa/validate', $data);
        } catch (ApiException $apiEx) {
            return match ($apiEx->getCode()) {
                StatusCodeInterface::STATUS_BAD_REQUEST => $this->badRequestReturned(
                    $data['reference_number'],
                    $apiEx->getMessage(),
                    $apiEx->getAdditionalData()
                ),
                StatusCodeInterface::STATUS_NOT_FOUND => $this->notFoundReturned(
                    $data['reference_number'],
                    $apiEx->getAdditionalData()
                ),
                default => throw $apiEx,
            };
        }

        $this->logger->notice(
            'Successfully matched LPA {uId} for account with Id {id} ',
            [
                'event_code' => EventCodes::OLDER_LPA_FOUND,
                'id'         => $userToken,
                'uId'        => $data['reference_number'],
            ]
        );

        return new AccessForAllApiResult(
            AccessForAllResult::FOUND,
            ($this->parseAccessForAllLpaMatchResponse)($response),
        );
    }

    public function confirm(
        string $userToken,
        int $lpaUid,
        string $firstnames,
        string $lastname,
        DateTimeInterface $dob,
        string $postcode,
        bool $forceActivationKey,
    ): AccessForAllApiResult {
        $data = [
            'reference_number'     => $lpaUid,
            'first_names'          => $firstnames,
            'last_name'            => $lastname,
            'dob'                  => $dob->format('Y-m-d'),
            'postcode'             => $postcode,
            'force_activation_key' => $forceActivationKey,
        ];

        $this->apiClient->setUserTokenHeader($userToken);

        try {
            $response = $this->apiClient->httpPatch('/v1/older-lpa/confirm', $data);
        } catch (ApiException $apiEx) {
            if ($apiEx->getMessage() === self::LPA_NEEDS_CLEANSING) {
                $this->logger->notice(
                    'Older LPA with id {uId} requires cleansing',
                    [
                        'event_code' => EventCodes::OLDER_LPA_NEEDS_CLEANSING,
                        'uId'        => $data['reference_number'],
                    ]
                );
                return new AccessForAllApiResult(
                    AccessForAllResult::OLDER_LPA_NEEDS_CLEANSING,
                    $apiEx->getAdditionalData()
                );
            }
            throw $apiEx;
        }

        $this->logger->notice(
            'Successfully matched LPA {uId} and requested letter for account with Id {id} ',
            [
                'event_code' => $forceActivationKey
                    ? EventCodes::OLDER_LPA_FORCE_ACTIVATION_KEY
                    : EventCodes::OLDER_LPA_SUCCESS,
                'id'         => $userToken,
                'uId'        => $data['reference_number'],
            ]
        );

        return new AccessForAllApiResult(AccessForAllResult::SUCCESS, $response);
    }

    /**
     * Translates an exception message returned from the API into a const string that we can use, as well
     * as logging the result
     *
     * @param int    $lpaUid
     * @param string $message
     * @param array  $additionalData
     * @return AccessForAllApiResult
     */
    private function badRequestReturned(int $lpaUid, string $message, array $additionalData): AccessForAllApiResult
    {
        switch ($message) {
            case self::LPA_ALREADY_ADDED:
                $code     = EventCodes::OLDER_LPA_ALREADY_ADDED;
                $response = new AccessForAllApiResult(
                    AccessForAllResult::LPA_ALREADY_ADDED,
                    ($this->parseLpaAlreadyAddedResponse)($additionalData)
                );
                break;

            case self::LPA_NOT_ELIGIBLE:
                $code     = EventCodes::OLDER_LPA_NOT_ELIGIBLE;
                $response = new AccessForAllApiResult(AccessForAllResult::NOT_ELIGIBLE, $additionalData);
                break;

            case self::LPA_DOES_NOT_MATCH:
                $code     = EventCodes::OLDER_LPA_DOES_NOT_MATCH;
                $response = new AccessForAllApiResult(AccessForAllResult::DOES_NOT_MATCH, $additionalData);
                break;

            case self::LPA_HAS_ACTIVATION_KEY:
                $code     = EventCodes::OLDER_LPA_HAS_ACTIVATION_KEY;
                $response = new AccessForAllApiResult(
                    AccessForAllResult::HAS_ACTIVATION_KEY,
                    ($this->parseActivationKeyExistsResponse)($additionalData)
                );
                break;

            case self::LPA_KEY_ALREADY_REQUESTED:
                $code     = EventCodes::OLDER_LPA_KEY_ALREADY_REQUESTED;
                $response = new AccessForAllApiResult(
                    AccessForAllResult::KEY_ALREADY_REQUESTED,
                    ($this->parseActivationKeyExistsResponse)($additionalData)
                );
                break;

            case self::LPA_POSTCODE_NOT_SUPPLIED:
                $code     = null;
                $response = new AccessForAllApiResult(AccessForAllResult::POSTCODE_NOT_SUPPLIED, $additionalData);
                break;

            case self::LPA_STATE_INVALID:
                $code     = null;
                $response = new AccessForAllApiResult(AccessForAllResult::STATUS_NOT_VALID, $additionalData);
                break;

            default:
                throw new RuntimeException(
                    'A bad request was made to add an older lpa and the reason for rejection is '
                    . 'not understood'
                );
        }

        $this->logger->notice(
            'LPA with reference number {uId} not added because "{reason}"',
            [
                'event_code' => $code,
                'uId'        => $lpaUid,
                'reason'     => $message,
            ]
        );

        return $response;
    }

    /**
     * Translates a 'Not Found' response from our API into an appropriate const value and also logs the result
     *
     * @param int   $lpaUid
     * @param array $additionalData
     * @return AccessForAllApiResult
     * @throws RuntimeException
     */
    private function notFoundReturned(int $lpaUid, array $additionalData): AccessForAllApiResult
    {
        $this->logger->notice(
            'LPA with reference number {uId} not found',
            [
                // attach an code for brute force checking
                'event_code' => EventCodes::OLDER_LPA_NOT_FOUND,
                'uId'        => $lpaUid,
            ]
        );

        return new AccessForAllApiResult(AccessForAllResult::NOT_FOUND, $additionalData);
    }
}
