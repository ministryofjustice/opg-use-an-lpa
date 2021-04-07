<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;

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

    private ArrayObject $data;
    private string $response;

    public function __construct(string $response, ArrayObject $data)
    {
        if (!$this->validateResponseType($response)) {
            throw new \RuntimeException('Incorrect response type when creating ' . __CLASS__);
        }
        $this->response = $response;
        $this->data = $data;
    }

    /**
     * @return ArrayObject
     */
    public function getData(): ArrayObject
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
            self::ADD_LPA_ALREADY_ADDED
        ];

        if (in_array($response, $allowedResponses)) {
            return true;
        }

        return false;
    }
}
