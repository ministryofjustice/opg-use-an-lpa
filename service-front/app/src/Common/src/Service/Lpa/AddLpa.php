<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAdded;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class AddLpa
{
    // Exception messages returned from the API layer
    private const ADD_LPA_NOT_ELIGIBLE  = 'LPA status is not registered';
    private const ADD_LPA_ALREADY_ADDED = 'LPA already added';

    public function __construct(
        private ApiClient $apiClient,
        private LoggerInterface $logger,
        private ParseLpaData $parseLpaData,
        private ParseLpaAlreadyAdded $parseLpaAlreadyAddedResponse,
    ) {
    }

    /**
     * @throws Exception
     */
    public function validate(
        string $userToken,
        string $activation_key,
        string $lpaUid,
        string $dob,
    ): AddLpaApiResult {
        $data = [
            'actor-code' => $activation_key,
            'uid'        => $lpaUid,
            'dob'        => $dob,
        ];

        $this->apiClient->setUserTokenHeader($userToken);

        try {
            $lpaData = $this->apiClient->httpPost('/v1/add-lpa/validate', $data);
        } catch (ApiException $apiEx) {
            return match ($apiEx->getCode()) {
                StatusCodeInterface::STATUS_BAD_REQUEST => $this->badRequestReturned(
                    $lpaUid,
                    $apiEx->getMessage(),
                    $apiEx->getAdditionalData()
                ),
                StatusCodeInterface::STATUS_NOT_FOUND => $this->notFoundReturned(
                    $lpaUid,
                    $apiEx->getAdditionalData()
                ),
                default => throw $apiEx,
            };
        }

        $this->logger->notice(
            'User {id} has found their LPA with Id {uId} using their activation key',
            [
                'event_code' => EventCodes::ADD_LPA_FOUND,
                'id'         => $userToken,
                'uId'        => $lpaUid,
            ]
        );

        $lpaData = ($this->parseLpaData)($lpaData);

        return new AddLpaApiResult(AddLpaApiResult::ADD_LPA_FOUND, $lpaData);
    }

    public function confirm(
        string $userToken,
        string $activation_key,
        string $lpaUid,
        string $dob,
    ): AddLpaApiResult {
        $this->apiClient->setUserTokenHeader($userToken);

        $lpaData = $this->apiClient->httpPost('/v1/add-lpa/confirm', [
            'actor-code' => $activation_key,
            'uid'        => $lpaUid,
            'dob'        => $dob,
        ]);

        if (isset($lpaData['user-lpa-actor-token'])) {
            $this->logger->notice(
                'Account with Id {id} added LPA with Id {uId} to their account',
                [
                    'event_code' => EventCodes::ADD_LPA_SUCCESS,
                    'id'         => $userToken,
                    'uId'        => $lpaUid,
                ]
            );

            return new AddLpaApiResult(AddLpaApiResult::ADD_LPA_SUCCESS, []);
        }

        $this->logger->notice(
            'An error occured when account with Id {id} confirmed adding their LPA with Id {uId} to their account',
            [
                'event_code' => EventCodes::ADD_LPA_FAILURE,
                'id'         => $userToken,
                'uId'        => $lpaUid,
            ]
        );

        return new AddLpaApiResult(AddLpaApiResult::ADD_LPA_FAILURE, []);
    }

    /**
     * Translates an exception message returned from the API into a const string that we can use, as well
     * as logging the result
     *
     * @param string $lpaUid
     * @param string $message
     * @param array  $additionalData
     * @return AddLpaApiResult
     */
    private function badRequestReturned(string $lpaUid, string $message, array $additionalData): AddLpaApiResult
    {
        switch ($message) {
            case self::ADD_LPA_NOT_ELIGIBLE:
                $code     = EventCodes::ADD_LPA_NOT_ELIGIBLE;
                $response = new AddLpaApiResult(
                    AddLpaApiResult::ADD_LPA_NOT_ELIGIBLE,
                    $additionalData
                );
                break;

            case self::ADD_LPA_ALREADY_ADDED:
                $code     = EventCodes::ADD_LPA_ALREADY_ADDED;
                $response = new AddLpaApiResult(
                    AddLpaApiResult::ADD_LPA_ALREADY_ADDED,
                    ($this->parseLpaAlreadyAddedResponse)($additionalData)
                );
                break;

            default:
                throw new RuntimeException(
                    'A bad request was made to add an lpa and the reason for rejection is '
                    . 'not understood'
                );
        }

        $this->logger->notice(
            'LPA with reference number {uId} was not added because "{reason}"',
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
     * @param string $lpaUid
     * @param array  $additionalData
     * @return AddLpaApiResult
     */
    private function notFoundReturned(string $lpaUid, array $additionalData): AddLpaApiResult
    {
        $this->logger->notice(
            'Validation failed on the details provided to add the LPA {uId}',
            [
                // attach a code for brute force checking
                'event_code' => EventCodes::ADD_LPA_NOT_FOUND,
                'uId'        => $lpaUid,
            ]
        );

        return new AddLpaApiResult(AddLpaApiResult::ADD_LPA_NOT_FOUND, $additionalData);
    }
}
