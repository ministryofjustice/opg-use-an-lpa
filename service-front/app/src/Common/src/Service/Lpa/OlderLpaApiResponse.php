<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

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
    /** @var string There is already an activation key available/in-flight */
    public const HAS_ACTIVATION_KEY_WITHIN_14_DAYS = 'HAS_ACTIVATION_KEY_WITHIN_14_DAYS';

    private array $data;
    private string $response;

    public function __construct(string $response, array $data)
    {
        if (!$this->validateResponseType($response)) {
            throw new \RuntimeException('Incorrect response type when creating ' . __CLASS__);
        }
        $this->response = $response;
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
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
            self::HAS_ACTIVATION_KEY_WITHIN_14_DAYS
        ];

        if (in_array($response, $allowedResponses)) {
            return true;
        }

        return false;
    }
}
