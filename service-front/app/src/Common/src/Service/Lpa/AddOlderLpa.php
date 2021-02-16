<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
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
    /** @var string The LPA was successfully added */
    public const SUCCESS            = 'SUCCESS';
    /** @var string The LPA reference number was not found */
    public const NOT_FOUND          = 'NOT_FOUND';
    /** @var string The LPA is not eligible to be added */
    public const NOT_ELIGIBLE       = 'NOT_ELIGIBLE';
    /** @var string The details provided do not match our records */
    public const DOES_NOT_MATCH     = 'NOT_MATCH';
    /** @var string There is already an activation key available/in-flight */
    public const HAS_ACTIVATION_KEY = 'HAS_ACTIVATION_KEY';

    // Exception messages returned from the API layer
    private const LPA_NOT_ELIGIBLE       = 'LPA not eligible due to registration date';
    private const LPA_DOES_NOT_MATCH     = 'LPA details do not match';
    private const LPA_HAS_ACTIVATION_KEY = 'LPA not eligible as an activation key already exists';

    /** @var ApiClient */
    private ApiClient $apiClient;
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * AddOlderLpa constructor.
     * @param ApiClient $apiClient
     * @param LoggerInterface $logger
     * @codeCoverageIgnore
     */
    public function __construct(ApiClient $apiClient, LoggerInterface $logger)
    {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    public function __invoke(
        string $userToken,
        int $lpaUid,
        string $firstnames,
        string $lastname,
        \DateTime $dob,
        string $postcode
    ): string {
        $data = [
            'reference_number'  => (string) $lpaUid,
            'first_names'       => $firstnames,
            'last_name'         => $lastname,
            'dob'               => $dob->format('Y-m-d'),
            'postcode'          => $postcode,
        ];

        $this->apiClient->setUserTokenHeader($userToken);

        try {
            $this->apiClient->httpPatch('/v1/lpas/request-letter', $data);
        } catch (ApiException $apiEx) {
            switch ($apiEx->getCode()) {
                case StatusCodeInterface::STATUS_BAD_REQUEST:
                    return $this->badRequestReturned($lpaUid, $apiEx->getMessage());
                case StatusCodeInterface::STATUS_NOT_FOUND:
                    return $this->notFoundReturned($lpaUid);
                default:
                    // An API exception that we don't want to handle has been caught, pass it up the stack
                    throw $apiEx;
            }
        }

        $this->logger->info(
            'Account with Id {id} requested older LPA addition of Id {uId}',
            [
                'id'  => $userToken,
                'uId' => $lpaUid
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Translates an exception message returned from the API into a const string that we can use, as well
     * as logging the result
     *
     * @param int    $lpaUid
     * @param string $message
     *
     * @return string
     * @throws RuntimeException
     */
    private function badRequestReturned(int $lpaUid, string $message): string
    {
        switch ($message) {
            case self::LPA_NOT_ELIGIBLE:
                $code = EventCodes::LPA_NOT_ELIGIBLE;
                $action = self::NOT_ELIGIBLE;
                break;

            case self::LPA_DOES_NOT_MATCH:
                $code = EventCodes::LPA_DOES_NOT_MATCH;
                $action = self::DOES_NOT_MATCH;
                break;

            case self::LPA_HAS_ACTIVATION_KEY:
                $code = EventCodes::LPA_HAS_ACTIVATION_KEY;
                $action = self::HAS_ACTIVATION_KEY;
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

        return $action;
    }

    /**
     * Translates a 'Not Found' response from our API into an appropriate const value and also logs the result
     *
     * @param int $lpaUid
     *
     * @return string
     */
    private function notFoundReturned(int $lpaUid): string
    {
        $this->logger->notice(
            'LPA with reference number {uId} not found',
            [
                // attach an code for brute force checking
                'event_code' => EventCodes::LPA_NOT_FOUND,
                'uId' => $lpaUid
            ]
        );

        return self::NOT_FOUND;
    }
}
