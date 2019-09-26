<?php

namespace Common\Service\Lpa;

use Common\Service\ApiClient\Client as ApiClient;
use ArrayObject;

/**
 * Class LpaService
 * @package Common\Service\ApiClient
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
     * @param string $lpaId
     * @return ArrayObject|null
     * @throws \Http\Client\Exception
     */
    public function getLpaById(string $lpaId) : ?ArrayObject
    {
        $lpaData = $this->apiClient->httpGet('/v1/lpa/' . $lpaId);

        if (is_array($lpaData)) {
            $lpaData = $this->parseLpaData($lpaData);
        }

        return $lpaData;
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
        //  Filter dashes out of the share code
        $shareCode = str_replace('-', '', $shareCode);

        $lpaData = $this->apiClient->httpPost('/v1/viewer-codes/summary', [
            'code' => $shareCode,
            'name' => 'Allen'       #TODO: Hard coded until form element is added.
        ]);

        if (is_array($lpaData)) {
            $lpaData = $this->parseLpaData($lpaData);
        }

        return $lpaData;
    }

    /**
     * Search for an LPA using credentials
     *
     * @param string $passcode
     * @param string $referenceNumber
     * @param string $dob
     * @return ArrayObject|null
     */
    public function search(string $passcode, string $referenceNumber, string $dob) : ?ArrayObject
    {
        $data = [
            'code' => $passcode,
            'uid'  => $referenceNumber,
            'dob'  => $dob,
        ];

        $lpaData = $this->apiClient->httpGet('/v1/lpa-search', $data);

        if (is_array($lpaData)) {
            $lpaData = $this->parseLpaData($lpaData);
        }

        return $lpaData;
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
