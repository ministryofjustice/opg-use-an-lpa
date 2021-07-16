<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;

class AddLpaApiResponse
{
    /** @var string The LPA was successfully found */
    public const ADD_LPA_FOUND         = 'ADD_LPA_FOUND';
    /** @var string An LPA could not be found with the details provided*/
    public const ADD_LPA_NOT_FOUND     = 'ADD_LPA_NOT_FOUND';
    /** @var string The LPA is not eligible to be added */
    public const ADD_LPA_NOT_ELIGIBLE  = 'ADD_LPA_NOT_ELIGIBLE';
    /** @var string The LPA could not be added as it already has been */
    public const ADD_LPA_ALREADY_ADDED = 'ADD_LPA_ALREADY_ADDED';
    /** @var string The LPA has been successfully added */
    public const ADD_LPA_SUCCESS = 'ADD_LPA_SUCCESS';
    /** @var string The LPA failed to be added */
    public const ADD_LPA_FAILURE = 'ADD_LPA_FAILURE';

    /** @var array|ArrayObject|LpaAlreadyAddedResponse */
    private $data;
    private string $response;

    /**
     * AddLpaApiResponse constructor.
     *
     * @param string $response
     * @param array|ArrayObject|LpaAlreadyAddedResponse $data
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
     * @return array|ArrayObject|LpaAlreadyAddedResponse
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
            self::ADD_LPA_FOUND,
            self::ADD_LPA_NOT_FOUND,
            self::ADD_LPA_NOT_ELIGIBLE,
            self::ADD_LPA_ALREADY_ADDED,
            self::ADD_LPA_SUCCESS,
            self::ADD_LPA_FAILURE
        ];

        if (in_array($response, $allowedResponses)) {
            return true;
        }

        return false;
    }

    private function validateDataType($data): bool
    {
        $allowedDataTypes = [
            ArrayObject::class,
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
