<?php

namespace Common\Service\Lpa;

use ArrayObject;
use Common\Entity\Lpa;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LpaService constructor.
     * @param ApiClient $apiClient
     * @param LpaFactory $lpaFactory
     */
    public function __construct(ApiClient $apiClient, LpaFactory $lpaFactory, LoggerInterface $logger)
    {
        $this->apiClient = $apiClient;
        $this->lpaFactory = $lpaFactory;
        $this->logger = $logger;
    }

    /**
     * Get the users currently registered LPAs
     *
     * @param string $userToken
     * @return ArrayObject|null
     * @throws Exception
     */
    public function getLpas(string $userToken): ?ArrayObject
    {
        $this->apiClient->setUserTokenHeader($userToken);

        $lpaData = $this->apiClient->httpGet('/v1/lpas');

        if (is_array($lpaData)) {
            $lpaData = $this->parseLpaData($lpaData);
        }

        $this->logger->info(
            'Account with Id {id} retrieved {count} LPA(s)',
            [
                'id'    => $userToken,
                'count' => count($lpaData)
            ]
        );

        return $lpaData;
    }

    /**
     * @param string $userToken
     * @param string $actorLpaToken
     * @return ArrayObject|null
     * @throws Exception
     */
    public function getLpaById(string $userToken, string $actorLpaToken): ?ArrayObject
    {
        $this->apiClient->setUserTokenHeader($userToken);

        $lpaData = $this->apiClient->httpGet('/v1/lpas/' . $actorLpaToken);

        $lpaData = isset($lpaData) ? $this->parseLpaData($lpaData) : null;

        if ($lpaData['lpa'] !== null) {
            $this->logger->info(
                'Account with Id {id} fetched LPA with Id {uId}',
                [
                    'id'  => $userToken,
                    'uId' => $lpaData['lpa']->getUId()
                ]
            );
        }

        return $lpaData;
    }

    /**
     * Get an LPA
     *
     * @param string $shareCode
     * @param string $donorSurname
     * @param string|null $organisation
     * @return ArrayObject|null
     * @throws Exception
     */
    public function getLpaByCode(string $shareCode, string $donorSurname, ?string $organisation = null): ?ArrayObject
    {
        //  Filter dashes out of the share code
        $shareCode = str_replace('-', '', $shareCode);
        $shareCode = str_replace(' ', '', $shareCode);
        $shareCode = strtoupper($shareCode);

        if (!is_null($organisation)) {
            $trackRoute = "full";
            $requestData = [
                'code' => $shareCode,
                'name' => $donorSurname,
                'organisation' => $organisation
            ];
        } else {
            $trackRoute = "summary";
            $requestData = [
                'code' => $shareCode,
                'name' => $donorSurname,
            ];
        }

        $this->logger->debug(
            'User requested {type} view of LPA by share code',
            [
                'type' => $trackRoute
            ]
        );

        try {
            $lpaData = $this->apiClient->httpPost(
                '/v1/viewer-codes/' . $trackRoute,
                $requestData
            );
        } catch (ApiException $apiEx) {
            switch ($apiEx->getCode()) {
                case StatusCodeInterface::STATUS_GONE:
                    if ($apiEx->getMessage() === 'Share code cancelled') {
                        $this->logger->notice(
                            'Share code {code} cancelled when attempting to fetch {type}',
                            [
                                'code' => $shareCode,
                                'type' => $trackRoute
                            ]
                        );
                    } else {
                        $this->logger->notice(
                            'Share code {code} expired when attempting to fetch {type}',
                            [
                            'code' => $shareCode,
                            'type' => $trackRoute
                            ]
                        );
                    }
                    break;

                case StatusCodeInterface::STATUS_NOT_FOUND:
                    $this->logger->notice(
                        'Share code not found when attempting to fetch {type}',
                        [
                            // attach an code for brute force checking
                            'event_code' => EventCodes::SHARE_CODE_NOT_FOUND,
                            'type' => $trackRoute
                        ]
                    );
            }

            // still throw the exception up to the caller since handling of the issue will be done there
            throw $apiEx;
        }

        if (is_array($lpaData)) {
            $lpaData = $this->parseLpaData($lpaData);

            $this->logger->info(
                'LPA with Id {uId} retrieved by share code',
                [
                    'uId' => ($lpaData->lpa)->getUId()
                ]
            );
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
    public function getLpaByPasscode(string $userToken, string $passcode, string $referenceNumber, string $dob): ?ArrayObject
    {
        $data = [
            'actor-code' => $passcode,
            'uid'        => $referenceNumber,
            'dob'        => $dob,
        ];

        $this->apiClient->setUserTokenHeader($userToken);
        $lpaData = $this->apiClient->httpPost('/v1/actor-codes/summary', $data);

        if (isset($lpaData['lpa'])) {
            $lpaData = $this->parseLpaData($lpaData);

            $this->logger->info(
                'Account with Id {id} fetched LPA with Id {uId} by passcode',
                [
                    'id'  => $userToken,
                    'uId' => ($lpaData->lpa)->getUId()
                ]
            );
            return $lpaData;
        }

        return null;
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
    public function confirmLpaAddition(string $userToken, string $passcode, string $referenceNumber, string $dob): ?string
    {
        $data = [
            'actor-code' => $passcode,
            'uid'        => $referenceNumber,
            'dob'        => $dob,
        ];

        $this->apiClient->setUserTokenHeader($userToken);

        $lpaData = $this->apiClient->httpPost('/v1/actor-codes/confirm', $data);

        if (isset($lpaData['user-lpa-actor-token'])) {
            $this->logger->info(
                'Account with Id {id} added LPA with Id {uId} to account by passcode',
                [
                    'id'  => $userToken,
                    'uId' => $referenceNumber
                ]
            );

            return $lpaData['user-lpa-actor-token'];
        }

        return null;
    }

    /**
     * Sorts LPAs alphabetically by donor's lastname
     * Donors with multiple LPAs are then grouped
     * Finally each donor's LPAs are sorted with HW LPAs first, then by the most recently added
     *
     * @param ArrayObject $lpas
     * @return ArrayObject
     */
    public function sortLpasInOrder(ArrayObject $lpas): ArrayObject
    {
        $lpas = $this->sortLpasByDonorSurname($lpas);
        $lpas = $this->groupLpasByDonor($lpas);
        return $this->sortGroupedDonorsLpasByTypeThenAddedDate($lpas);
    }

    /**
     * Sorts a list of LPA's in alphabetical order by the donor's surname
     *
     * @param ArrayObject $lpas
     * @return ArrayObject
     */
    public function sortLpasByDonorSurname(ArrayObject $lpas): ArrayObject
    {
        $lpas = $lpas->getArrayCopy();

        uasort($lpas, function ($a, $b) {
            $surnameA = $a->lpa->getDonor()->getSurname();
            $surnameB = $b->lpa->getDonor()->getSurname();
            if ($surnameA === $surnameB) {
                // Compare firstnames if surnames are the same
                return strcmp($a->lpa->getDonor()->getFirstname(), $b->lpa->getDonor()->getFirstname());
            }
            return strcmp($surnameA, $surnameB);
        });

        return new ArrayObject($lpas, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Groups LPAs by Donor name as key
     *
     * @param ArrayObject $lpas
     * @return ArrayObject
     */
    public function groupLpasByDonor(ArrayObject $lpas): ArrayObject
    {
        $lpas = $lpas->getArrayCopy();

        $donors = [];
        foreach ($lpas as $userLpaToken => $lpa) {
            $donor = implode(" ", array_filter(
                [
                    $lpa->lpa->getDonor()->getFirstname(),
                    $lpa->lpa->getDonor()->getMiddlenames(),
                    $lpa->lpa->getDonor()->getSurname(),
                    ($lpa->lpa->getDonor()->getDob())->format('Y-m-d') //prevents different donors with name from being grouped together
                ]
            ));

            if (array_key_exists($donor, $donors)) {
                $donors[$donor][$userLpaToken] = $lpa;
            } else {
                $donors[$donor] = [$userLpaToken => $lpa];
            }
        }

        return new ArrayObject($donors, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Sorts each donors LPAs with HW type first, followed by most recently added
     *
     * @param ArrayObject $donors
     * @return ArrayObject
     */
    public function sortGroupedDonorsLpasByTypeThenAddedDate(ArrayObject $donors): ArrayObject
    {
        $donors = $donors->getArrayCopy();

        foreach ($donors as $donor => $donorsLpas) {
            if (sizeof($donorsLpas) > 1) {
                foreach ($donorsLpas as $lpaKey => $lpaData) {
                    uasort($donors[$donor], function ($keyA, $keyB) {
                        $lpaAType = $keyA->lpa->getCaseSubtype();
                        $lpaBType = $keyB->lpa->getCaseSubtype();
                        if ($lpaAType === $lpaBType) {
                            return $keyA->added >= $keyB->added ? -1 : 1;
                        }
                        return strcmp($lpaAType, $lpaBType);
                    });
                }
            }
        }

        return new ArrayObject($donors, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * @param string $referenceNumber
     * @param string $identity
     * @return bool|string
     * @throws Exception
     */
    public function isLpaAlreadyAdded(string $referenceNumber, string $identity)
    {
        $lpasAdded = $this->getLpas($identity);

        foreach ($lpasAdded as $userLpaActorToken => $lpaData) {
            if ($lpaData['lpa']->getUId() === $referenceNumber) {
                $this->logger->info(
                    'Account with Id {id} has attempted to add LPA {uId} which already exists in their account',
                    [
                        'id' => $identity,
                        'uId' => $referenceNumber
                    ]
                );
                return $userLpaActorToken;
            }
        }
        return false;
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
    private function parseLpaData(array $data): ArrayObject
    {
        foreach ($data as $dataItemName => $dataItem) {
            switch ($dataItemName) {
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

    /**
     * Check an LPA match
     *
     * @param string $userToken
     * @param array $data
     * @return null|string
     * @throws Exception
     */
    public function checkLPAMatchAndRequestLetter(string $userToken, array $data): ?string
    {
        $this->apiClient->setUserTokenHeader($userToken);

        try {
            $matchResponse = $this->apiClient->httpPatch('/v1/lpas/request-letter', $data);
        } catch (ApiException $apiEx) {
            switch ($apiEx->getCode()) {
                case StatusCodeInterface::STATUS_BAD_REQUEST:
                    if ($apiEx->getMessage() === 'LPA not eligible') {
                        $this->logger->notice(
                            'LPA with reference number {uId} not eligible to request activation key',
                            [
                                'event_code' => EventCodes::LPA_NOT_ELIGIBLE,
                                'uId' => $data['reference_number'],
                            ]
                        );
                    } elseif ($apiEx->getMessage() === 'LPA details does not match') {
                            $this->logger->notice(
                                'LPA with reference number {uId} does not match with user provided data',
                                [
                                    'event_code' => EventCodes::LPA_NOT_ELIGIBLE,
                                    'uId' => $data['reference_number'],
                                ]
                            );
                    } else {
                        $this->logger->notice(
                            'LPA with reference number {uId} already has an activation key',
                            [
                                'event_code' => EventCodes::LPA_HAS_ACTIVATION_KEY,
                                'uId' => $data['reference_number'],
                            ]
                        );
                    }
                    break;

                case StatusCodeInterface::STATUS_NOT_FOUND:
                    $this->logger->notice(
                        'LPA with reference number {uId} not found',
                        [
                            // attach an code for brute force checking
                            'event_code' => EventCodes::LPA_NOT_FOUND,
                            'uId' => $data['reference_number']
                        ]
                    );
            }
            // still throw the exception up to the caller since handling of the issue will be done there
            throw $apiEx;
        }
        if (!null($matchResponse)) {
            $this->logger->info(
                'Account with Id {id} added LPA with Id {uId} to account by passcode',
                [
                    'id'  => $userToken,
                    'uId' => $data['reference_number']
                ]
            );
        }
        return $matchResponse;
    }
}
