<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use RuntimeException;

class AddLpaApiResponse
{
    public const ADD_LPA_FOUND         = 'ADD_LPA_FOUND';
    public const ADD_LPA_NOT_FOUND     = 'ADD_LPA_NOT_FOUND';
    public const ADD_LPA_NOT_ELIGIBLE  = 'ADD_LPA_NOT_ELIGIBLE';
    public const ADD_LPA_ALREADY_ADDED = 'ADD_LPA_ALREADY_ADDED';
    public const ADD_LPA_SUCCESS       = 'ADD_LPA_SUCCESS';
    public const ADD_LPA_FAILURE       = 'ADD_LPA_FAILURE';

    private array|ArrayObject|LpaAlreadyAddedResponse $data;

    /**
     * @param string $response
     * @param array|ArrayObject|LpaAlreadyAddedResponse $data
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
     * @return array|ArrayObject|LpaAlreadyAddedResponse
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
            self::ADD_LPA_FOUND,
            self::ADD_LPA_NOT_FOUND,
            self::ADD_LPA_NOT_ELIGIBLE,
            self::ADD_LPA_ALREADY_ADDED,
            self::ADD_LPA_SUCCESS,
            self::ADD_LPA_FAILURE,
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
            if (in_array($data::class, $allowedDataTypes)) {
                return true;
            }
        }

        return false;
    }
}
