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
     * @param int $lpaId
     * @return ArrayObject|null
     * @throws \Http\Client\Exception
     */
    public function getLpaById(int $lpaId) : ?ArrayObject
    {
        $lpaData = $this->apiClient->httpGet('/lpa', [
            'id' => $lpaId,
        ]);

        if (is_array($lpaData)) {
            return $this->parseLpaData($lpaData);
        }

        return null;
    }

    /**
     * Get an LPA
     *
     * @param string $shareCode
     * @return ArrayObject|null
     * @throws \Http\Client\Exception
     */
    public function getLpaByCode(string $shareCode) : ?ArrayObject
    {
        $lpaData = $this->apiClient->httpGet('/lpa-by-code', [
            'code' => $shareCode,
        ]);

        if (is_array($lpaData)) {
            return $this->parseLpaData($lpaData);
        }

        return null;
    }

    /**
     * @param array $data
     * @return ArrayObject
     */
    private function parseLpaData(array $data): ArrayObject
    {
        foreach ($data as $dataItemName => $dataItem) {
            if (is_array($dataItem)) {
                $data[$dataItemName] = $this->parseLpaData($dataItem);
            }
        }

        //  TODO - Transform the data array into a data object
        return new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS);
    }
}
