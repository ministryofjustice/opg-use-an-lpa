<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Service\Lpa\Response\ActivationKeyExistsResponse;
use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use Common\Service\Lpa\Response\OlderLpaMatchResponse;
use RuntimeException;

class OlderLpaApiResponse
{
    public const SUCCESS                   = 'SUCCESS';
    public const FOUND                     = 'FOUND';
    public const NOT_FOUND                 = 'NOT_FOUND';
    public const NOT_ELIGIBLE              = 'NOT_ELIGIBLE';
    public const DOES_NOT_MATCH            = 'NOT_MATCH';
    public const HAS_ACTIVATION_KEY        = 'HAS_ACTIVATION_KEY';
    public const LPA_ALREADY_ADDED         = 'LPA_ALREADY_ADDED';
    public const OLDER_LPA_NEEDS_CLEANSING = 'OLDER_LPA_NEEDS_CLEANSING';
    public const KEY_ALREADY_REQUESTED     = 'KEY_ALREADY_REQUESTED';

    private array|ActivationKeyExistsResponse|LpaAlreadyAddedResponse|OlderLpaMatchResponse $data;

    /**
     * @param string $response
     * @param array|ActivationKeyExistsResponse|LpaAlreadyAddedResponse|OlderLpaMatchResponse $data
     */
    public function __construct(private string $response, $data)
    {
        if (!$this->validateResponseType($response)) {
            throw new RuntimeException('Incorrect response type when creating ' . self::class);
        }

        if (!$this->validateDataType($data)) {
            throw new RuntimeException('Incorrect data type when creating ' . self::class);
        }

        $this->data = $data;
    }

    /**
     * @return array|ActivationKeyExistsResponse|LpaAlreadyAddedResponse|OlderLpaMatchResponse
     */
    public function getData()
    {
        return $this->data;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    private function validateResponseType(string $response): bool
    {
        $allowedResponses = [
            self::SUCCESS,
            self::FOUND,
            self::NOT_FOUND,
            self::DOES_NOT_MATCH,
            self::NOT_ELIGIBLE,
            self::HAS_ACTIVATION_KEY,
            self::LPA_ALREADY_ADDED,
            self::OLDER_LPA_NEEDS_CLEANSING,
            self::KEY_ALREADY_REQUESTED,
        ];

        if (in_array($response, $allowedResponses)) {
            return true;
        }

        return false;
    }

    private function validateDataType($data): bool
    {
        $allowedDataTypes = [
            ActivationKeyExistsResponse::class,
            LpaAlreadyAddedResponse::class,
            OlderLpaMatchResponse::class,
        ];

        if (is_array($data)) {
            return true;
        }

        if (is_object($data)) {
            if (in_array($data::class, $allowedDataTypes)) {
                return true;
            }
        }

        return false;
    }
}
