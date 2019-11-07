<?php

namespace Common\Service\Lpa;

use Common\Entity\Lpa;
use Common\Service\ApiClient\Client as ApiClient;
use ArrayObject;
use Exception;

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
     * Get the users currently registered LPAs
     *
     * @param string $userToken
     * @return ArrayObject|null
     * @throws Exception
     */
    public function getLpas(string $userToken) : ?ArrayObject
    {
        $this->apiClient->setUserTokenHeader($userToken);

        $lpaData = $this->apiClient->httpGet('/v1/lpas');

        if (is_array($lpaData)) {
            $lpaData = $this->parseLpaData($lpaData);
        }

        return $lpaData;
    }

    /**
     * @param string $userToken
     * @param string $actorLpaToken
     * @return Lpa|null
     * @throws Exception
     */
    public function getLpaById(string $userToken, string $actorLpaToken) : ?Lpa
    {
        $this->apiClient->setUserTokenHeader($userToken);

        $lpaData = $this->apiClient->httpGet('/v1/lpas/' . $actorLpaToken);

        return isset($lpaData['lpa']) ? $this->lpaFactory->createLpaFromData($lpaData['lpa']) : null;
    }

    /**
     * Get an LPA
     *
     * @param string $shareCode
     * @param string $donorSurname
     * @return ArrayObject|null
     * @throws Exception
     */
    public function getLpaByCode(string $shareCode, string $donorSurname, bool $track) : ?ArrayObject
    {
        //  Filter dashes out of the share code
        $shareCode = str_replace('-', '', $shareCode);

        if($track){
            $trackRoute = 'full';
        } else {
            $trackRoute = 'summary';
        }

        $lpaData = $this->apiClient->httpPost('/v1/viewer-codes/' . $trackRoute, [
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
     * @param string $userToken
     * @param string $passcode
     * @param string $referenceNumber
     * @param string $dob
     * @return Lpa|null
     * @throws Exception
     */
    public function getLpaByPasscode(string $userToken, string $passcode, string $referenceNumber, string $dob) : ?Lpa
    {
        $data = [
            'actor-code' => $passcode,
            'uid'        => $referenceNumber,
            'dob'        => $dob,
        ];

        $this->apiClient->setUserTokenHeader($userToken);

        // TODO $lpaData also contains an CaseActor 'actor' that we should probably return
        $lpaData = $this->apiClient->httpPost('/v1/actor-codes/summary', $data);

        return isset($lpaData['lpa']) ? $this->lpaFactory->createLpaFromData($lpaData['lpa']) : null;
    }

    /**
     * Confirm the addition of an LPA to an actors UaLPA account
     *
     * @param string $userToken
     * @param string $passcode
     * @param string $referenceNumber
     * @param string $dob
     * @return string|null The unique actor token that links an actor record and lpa together
     */
    public function confirmLpaAddition(string $userToken, string $passcode, string $referenceNumber, string $dob) : ?string
    {
        $data = [
            'actor-code' => $passcode,
            'uid'        => $referenceNumber,
            'dob'        => $dob,
        ];

        $this->apiClient->setUserTokenHeader($userToken);

        $lpaData = $this->apiClient->httpPost('/v1/actor-codes/confirm', $data);

        return $lpaData['user-lpa-actor-token'] ?? null;
    }

    /**
     * Attempts to convert the data arrays received via the various endpoints into an ArrayObject containing
     * scalar and object values.
     *
     * Currently fairly naive in its assumption that the data types are stored under explicit keys, which
     * may change.
     *
     * @param array $data
     * @return ArrayObject
     * @throws Exception
     */
    private function parseLpaData(array $data) : ArrayObject
    {
        foreach ($data as $dataItemName => $dataItem) {
            switch($dataItemName) {
                case 'lpa':
                    $data['lpa'] = $this->lpaFactory->createLpaFromData($dataItem);
                    break;
                case 'actor':
                    $data['actor']['details'] = $this->lpaFactory->createCaseActorFromData($dataItem['details']);
                    break;
                default:
                    if (is_array($dataItem)) {
                        $data[$dataItemName] = $this->parseLpaData($dataItem);
                    }
            }
        }

        return new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS);
    }
}
