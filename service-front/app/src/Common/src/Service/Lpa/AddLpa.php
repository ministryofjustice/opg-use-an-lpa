<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Fig\Http\Message\StatusCodeInterface;
use ArrayObject;
use Psr\Log\LoggerInterface;
use RuntimeException;

class AddLpa
{
    // Exception messages returned from the API layer
    private const ADD_LPA_NOT_FOUND     = 'Code validation failed';
    private const ADD_LPA_NOT_ELIGIBLE  = 'LPA status is not registered';
    private const ADD_LPA_ALREADY_ADDED = 'LPA already added';

    private LpaService $lpaService;
    private LoggerInterface $logger;
    private ApiClient $apiClient;
    private ParseLpaData $parseLpaData;

    public function __construct(
        ApiClient $apiClient,
        LpaService $lpaService,
        LoggerInterface $logger,
        ParseLpaData $parseLpaData
    ) {
        $this->apiClient = $apiClient;
        $this->lpaService = $lpaService;
        $this->logger = $logger;
        $this->parseLpaData = $parseLpaData;
    }

    public function validateAddLpaData(
        string $userToken,
        string $passcode,
        string $lpaUid,
        string $dob
    ): AddLpaApiResponse {
        $data = [
            'actor-code' => $passcode,
            'uid' => $lpaUid,
            'dob' => $dob
        ];

        $this->apiClient->setUserTokenHeader($userToken);

        try {
            $lpaData = $this->apiClient->httpPost('/v1/add-lpa/validate', $data);
        } catch (ApiException $apiEx) {
            switch ($apiEx->getCode()) {
                case StatusCodeInterface::STATUS_BAD_REQUEST:
                    return $this->badRequestReturned(
                        $lpaUid,
                        $apiEx->getMessage(),
                        ($this->parseLpaData)($apiEx->getAdditionalData())
                    );
                case StatusCodeInterface::STATUS_NOT_FOUND:
                    return $this->notFoundReturned(
                        $lpaUid,
                        ($this->parseLpaData)($apiEx->getAdditionalData())
                    );
                default:
                    // An API exception that we don't want to handle has been caught, pass it up the stack
                    throw $apiEx;
            }
        }

        $this->logger->notice(
            'User {id} has found their LPA with Id {uId} using their activation key',
            [
                'id'  => $userToken,
                'uId' => $lpaUid
            ]
        );

        $lpaData = ($this->parseLpaData)($lpaData);

        return new AddLpaApiResponse(AddLpaApiResponse::ADD_LPA_FOUND, $lpaData);
    }

    public function confirmAddingLpa(
        string $userToken,
        string $passcode,
        string $lpaUid,
        string $dob
    ): AddLpaApiResponse {
        $this->apiClient->setUserTokenHeader($userToken);

        $lpaData = $this->apiClient->httpPost('/v1/add-lpa/confirm', [
            'actor-code' => $passcode,
            'uid'        => $lpaUid,
            'dob'        => $dob,
        ]);

        if (isset($lpaData['user-lpa-actor-token'])) {
            $this->logger->notice(
                'Account with Id {id} added LPA with Id {uId} to their account',
                [
                    'id'  => $userToken,
                    'uId' => $lpaUid
                ]
            );

            return new AddLpaApiResponse(AddLpaApiResponse::ADD_LPA_SUCCESS, new ArrayObject());
        }

        return new AddLpaApiResponse(AddLpaApiResponse::ADD_LPA_FAILURE, new ArrayObject());
    }

    /**
     * Translates an exception message returned from the API into a const string that we can use, as well
     * as logging the result
     *
     * @param string      $lpaUid
     * @param string      $message
     * @param ArrayObject $additionalData
     *
     * @return AddLpaApiResponse
     */
    private function badRequestReturned(string $lpaUid, string $message, ArrayObject $additionalData): AddLpaApiResponse
    {
        switch ($message) {
            case self::ADD_LPA_NOT_ELIGIBLE:
                $code = EventCodes::ADD_LPA_NOT_ELIGIBLE;
                $response = new AddLpaApiResponse(AddLpaApiResponse::ADD_LPA_NOT_ELIGIBLE, $additionalData);
                break;

            case self::ADD_LPA_ALREADY_ADDED:
                $code = EventCodes::ADD_LPA_ALREADY_ADDED;
                $response = new AddLpaApiResponse(AddLpaApiResponse::ADD_LPA_ALREADY_ADDED, $additionalData);
                break;

            default:
                throw new RuntimeException(
                    'A bad request was made to add an lpa and the reason for rejection is '
                    . 'not understood'
                );
        }

        $this->logger->notice(
            'LPA with reference number {uId} not added because "{reason}"',
            [
                'event_code' => $code,
                'uId' => $lpaUid,
                'reason' => $message,
            ]
        );

        return $response;
    }

    /**
     * Translates a 'Not Found' response from our API into an appropriate const value and also logs the result
     *
     * @param string      $lpaUid
     * @param ArrayObject $additionalData
     *
     * @return AddLpaApiResponse
     */
    private function notFoundReturned(string $lpaUid, ArrayObject $additionalData): AddLpaApiResponse
    {
        $this->logger->notice(
            'Validation failed on the details provided to add the LPA {uId}',
            [
                // attach a code for brute force checking
                'event_code' => EventCodes::ADD_LPA_NOT_FOUND,
                'uId' => $lpaUid
            ]
        );

        return new AddLpaApiResponse(AddLpaApiResponse::ADD_LPA_NOT_FOUND, $additionalData);
    }
}
