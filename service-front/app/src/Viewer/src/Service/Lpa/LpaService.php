<?php

namespace Viewer\Service\Lpa;

use Viewer\Service\ApiClient\Client as ApiClient;
use ArrayObject;

/**
 * Class LpaService
 * @package Viewer\Service\ApiClient
 */
class LpaService
{
    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * LpaService constructor.
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Get an LPA
     *
     * @param string $shareCode
     * @return ArrayObject|null
     * @throws \Http\Client\Exception
     */
    public function getLpa(string $shareCode) : ?ArrayObject
    {
        $lpaData = $this->apiClient->httpGet('/path/to/lpa', [
            'code' => $shareCode,
        ]);

        if (is_array($lpaData)) {
            //  TODO - Transform the data array into a data object
            return new ArrayObject($lpaData);
        }

        return null;
    }
}
