<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Service\Lpa\Response\ActivationKeyExistsResponse;
use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;

class OlderLpaApiResponse
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
    /** @var string The LPA has already been added to the account */
    public const LPA_ALREADY_ADDED  = 'LPA_ALREADY_ADDED';

    /** @var array|ActivationKeyExistsResponse|LpaAlreadyAddedResponse */
    private $data;
    private string $response;

    /**
     * OlderLpaApiResponse constructor.
     *
     * @param string $response
     * @param array|ActivationKeyExistsResponse|LpaAlreadyAddedResponse $data
     */
    public function __construct(string $response, $data)
    {
        if (!$this->validateResponseType($response)) {
            throw new \RuntimeException('Incorrect response type when creating ' . __CLASS__);
        }

        if (!$this->validateDataType($data)) {
            throw new \RuntimeException('Incorrect data type when creating ' . __CLASS__);
        }

        $this->response = $response;
        $this->data = $data;
    }

    /**
     * @return array|ActivationKeyExistsResponse|LpaAlreadyAddedResponse
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    private function validateResponseType(string $response): bool
    {
        $allowedResponses = [
            self::SUCCESS,
            self::NOT_FOUND,
            self::DOES_NOT_MATCH,
            self::NOT_ELIGIBLE,
            self::HAS_ACTIVATION_KEY,
            self::LPA_ALREADY_ADDED
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
        ];

        if (is_array($data)) {
            return true;
        }

        if (is_object($data)) {
            if (in_array(get_class($data), $allowedDataTypes)) {
                return true;
            }
        }

        return false;
    }
}
