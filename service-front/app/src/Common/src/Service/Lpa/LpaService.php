<?php

namespace Common\Service\Lpa;

use Common\Entity\Lpa;
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
     * @var LpaFactory
     */
    private $lpaFactory;

    /**
     * LpaService constructor.
     * @param ApiClient $apiClient
     * @param LpaFactory $lpaFactory
     */
    public function __construct(ApiClient $apiClient, LpaFactory $lpaFactory)
    {
        $this->apiClient = $apiClient;
        $this->lpaFactory = $lpaFactory;
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
     * @param string $donorSurname
     * @return ArrayObject|null
     */
    public function getLpaByCode(string $shareCode, string $donorSurname) : ?ArrayObject
    {
        //  Filter dashes out of the share code
        $shareCode = str_replace('-', '', $shareCode);

        $lpaData = $this->apiClient->httpPost('/v1/viewer-codes/summary', [
            'code' => $shareCode,
            'name' => $donorSurname,
        ]);

        if (is_array($lpaData)) {
            $lpaData = $this->parseLpaData($lpaData);
        }

        return $lpaData;
    }

    /**
     * Get an LPA using a users supplied one time passcode, LPA uid and the actors DoB
     *
     * Used when an actor adds an LPA to their UaLPA account
     *
     * @param string $passcode
     * @param string $referenceNumber
     * @param string $dob
     * @return Lpa|null
     */
    public function getLpaByPasscode(string $passcode, string $referenceNumber, string $dob) : ?Lpa
    {
        $data = [
            'actor-code' => $passcode,
            'uid'        => $referenceNumber,
            'dob'        => $dob,
        ];

        $lpaData = $this->apiClient->httpPost('/v1/actor-codes/summary', $data);

        if (is_array($lpaData)) {
            return $this->lpaFactory->createLpaFromData($lpaData['lpa']);
        }

        return null;
    }

    /**
     * Confirm the addition of an LPA to an actors UaLPA account
     *
     * @param string $passcode
     * @param string $referenceNumber
     * @param string $dob
     * @return string|null The unique actor token that links an actor record and lpa together
     */
    public function confirmLpaAddition(string $passcode, string $referenceNumber, string $dob) : ?string
    {
        $data = [
            'actor-code' => $passcode,
            'uid'        => $referenceNumber,
            'dob'        => $dob,
        ];

        $lpaData = $this->apiClient->httpPost('/v1/actor-codes/confirm', $data);

        if (is_array($lpaData)) {
            return $lpaData['user-lpa-actor-token'] ?? null;
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

        return new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS);
    }
}
