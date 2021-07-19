<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Common\Service\Lpa\Response\Parse\ParseActivationKeyExistsResponse;
use Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAddedResponse;
use DateTimeInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class AddOlderLpa
 *
 * Single action invokeable class that is responsible for calling the APIs necessary to add older
 * LPAs to a users account.
 *
 * @package Common\Service\Lpa
 */
class AddOlderLpa
{
    // Exception messages returned from the API layer
    private const OLDER_LPA_NOT_ELIGIBLE        = 'LPA not eligible due to registration date';
    private const OLDER_LPA_DOES_NOT_MATCH      = 'LPA details do not match';
    private const OLDER_LPA_HAS_ACTIVATION_KEY  = 'LPA has an activation key already';
    private const OLDER_LPA_ALREADY_ADDED       = 'LPA already added';

    /** @var ApiClient */
    private ApiClient $apiClient;
    /** @var LoggerInterface */
    private LoggerInterface $logger;
    /** @var ParseLpaAlreadyAddedResponse */
    private ParseLpaAlreadyAddedResponse $parseLpaAlreadyAddedResponse;
    /** @var ParseActivationKeyExistsResponse */
    private ParseActivationKeyExistsResponse $parseActivationKeyExistsResponse;

    /**
     * AddOlderLpa constructor.
     *
     * @param ApiClient       $apiClient
     * @param LoggerInterface $logger
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        ApiClient $apiClient,
        LoggerInterface $logger,
        ParseLpaAlreadyAddedResponse $parseLpaAlreadyAddedResponse,
        ParseActivationKeyExistsResponse $parseActivationKeyExistsResponse
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->parseLpaAlreadyAddedResponse = $parseLpaAlreadyAddedResponse;
        $this->parseActivationKeyExistsResponse = $parseActivationKeyExistsResponse;
    }

    public function __invoke(
        string $userToken,
        int $lpaUid,
        string $firstnames,
        string $lastname,
        DateTimeInterface $dob,
        string $postcode,
        bool $forceActivationKey = false,
        bool $requestActivationKeyLetter = false
    ): OlderLpaApiResponse {
        $data = [
            'reference_number'          => $lpaUid,
            'first_names'               => $firstnames,
            'last_name'                 => $lastname,
            'dob'                       => $dob->format('Y-m-d'),
            'postcode'                  => $postcode,
            'force_activation_key'      => $forceActivationKey,
            'request_activation_key'    => $requestActivationKeyLetter
        ];

        $this->apiClient->setUserTokenHeader($userToken);

        try {
            $lpaData = $this->apiClient->httpPatch('/v1/lpas/request-letter', $data);

        } catch (ApiException $apiEx) {
            switch ($apiEx->getCode()) {
                case StatusCodeInterface::STATUS_BAD_REQUEST:
                    return $this->badRequestReturned(
                        $data['reference_number'],
                        $apiEx->getMessage(),
                        $apiEx->getAdditionalData()
                    );
                case StatusCodeInterface::STATUS_NOT_FOUND:
                    return $this->notFoundReturned(
                        $data['reference_number'],
                        $apiEx->getAdditionalData()
                    );
                default:
                    // An API exception that we don't want to handle has been caught, pass it up the stack
                    throw $apiEx;
            }
        }

        if (!empty($lpaData)) {
            return new OlderLpaApiResponse(OlderLpaApiResponse::ADD_LPA_FOUND, $lpaData);
        }

        $eventCode = ($forceActivationKey) ? EventCodes::OLDER_LPA_FORCE_ACTIVATION_KEY : EventCodes::OLDER_LPA_SUCCESS;
        $this->logger->notice(
            'Successfully matched LPA {uId} and sending activation letter for account with Id {id} ',
            [
                'event_code' => $eventCode,
                'id'  => $data['identity'],
                'uId' => $data['reference_number']
            ]
        );

        return new OlderLpaApiResponse(OlderLpaApiResponse::SUCCESS, []);
    }

    /**
     * Translates an exception message returned from the API into a const string that we can use, as well
     * as logging the result
     *
     * @param int    $lpaUid
     * @param string $message
     * @param array  $additionalData
     *
     * @return OlderLpaApiResponse
     * @throws RuntimeException
     */
    private function badRequestReturned(int $lpaUid, string $message, array $additionalData): OlderLpaApiResponse
    {
        switch ($message) {
            case self::OLDER_LPA_ALREADY_ADDED:
                $code = EventCodes::OLDER_LPA_ALREADY_ADDED;
                $response = new OlderLpaApiResponse(
                    OlderLpaApiResponse::LPA_ALREADY_ADDED,
                    ($this->parseLpaAlreadyAddedResponse)($additionalData)
                );
                break;

            case self::OLDER_LPA_NOT_ELIGIBLE:
                $code = EventCodes::OLDER_LPA_NOT_ELIGIBLE;
                $response = new OlderLpaApiResponse(OlderLpaApiResponse::NOT_ELIGIBLE, $additionalData);
                break;

            case self::OLDER_LPA_DOES_NOT_MATCH:
                $code = EventCodes::OLDER_LPA_DOES_NOT_MATCH;
                $response = new OlderLpaApiResponse(OlderLpaApiResponse::DOES_NOT_MATCH, $additionalData);
                break;

            case self::OLDER_LPA_HAS_ACTIVATION_KEY:
                $code = EventCodes::OLDER_LPA_HAS_ACTIVATION_KEY;
                $response = new OlderLpaApiResponse(
                    OlderLpaApiResponse::HAS_ACTIVATION_KEY,
                    ($this->parseActivationKeyExistsResponse)($additionalData)
                );
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
                'uId' => $lpaUid,
                'reason' => $message,
            ]
        );

        return $response;
    }

    /**
     * Translates a 'Not Found' response from our API into an appropriate const value and also logs the result
     *
     * @param int   $lpaUid
     * @param array $additionalData
     *
     * @return OlderLpaApiResponse
     * @throws RuntimeException
     */
    private function notFoundReturned(int $lpaUid, array $additionalData): OlderLpaApiResponse
    {
        $this->logger->notice(
            'LPA with reference number {uId} not found',
            [
                // attach an code for brute force checking
                'event_code' => EventCodes::OLDER_LPA_NOT_FOUND,
                'uId' => $lpaUid
            ]
        );

        return new OlderLpaApiResponse(OlderLpaApiResponse::NOT_FOUND, $additionalData);
    }
}
