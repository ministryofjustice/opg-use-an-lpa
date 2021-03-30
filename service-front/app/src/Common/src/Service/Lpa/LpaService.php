<?php

declare(strict_types=1);

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
    /** @var ApiClient */
    private $apiClient;
    /** @var GroupLpas */
    private GroupLpas $groupLpas;
    /** @var LoggerInterface */
    private $logger;
    /** @var ParseLpaData */
    private $parseLpaData;
    /** @var PopulateLpaMetadata */
    private PopulateLpaMetadata $populateLpaMetadata;
    /** @var SortLpas */
    private SortLpas $sortLpas;

    /**
     * LpaService constructor.
     *
     * @param ApiClient           $apiClient
     * @param ParseLpaData        $parseLpaData
     * @param PopulateLpaMetadata $populateLpaMetadata
     * @param SortLpas            $sortLpas
     * @param GroupLpas           $groupLpas
     * @param LoggerInterface     $logger
     */
    public function __construct(
        ApiClient $apiClient,
        ParseLpaData $parseLpaData,
        PopulateLpaMetadata $populateLpaMetadata,
        SortLpas $sortLpas,
        GroupLpas $groupLpas,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->parseLpaData = $parseLpaData;
        $this->populateLpaMetadata = $populateLpaMetadata;
        $this->logger = $logger;
        $this->sortLpas = $sortLpas;
        $this->groupLpas = $groupLpas;
    }

    /**
     * Get the users currently registered LPAs
     *
     * @param string $userToken
     * @param bool   $sortAndPopulate Sort group and populate metadata for LPA dashboard
     *
     * @return ArrayObject|null
     * @throws Exception
     */
    public function getLpas(string $userToken, bool $sortAndPopulate = false): ?ArrayObject
    {
        $this->apiClient->setUserTokenHeader($userToken);

        $lpaData = $this->apiClient->httpGet('/v1/lpas');

        if (is_array($lpaData)) {
            $lpaData = ($this->parseLpaData)($lpaData);
        }

        $this->logger->info(
            'Account with Id {id} retrieved {count} LPA(s)',
            [
                'id'    => $userToken,
                'count' => count($lpaData)
            ]
        );

        return $sortAndPopulate
            ? ($this->groupLpas)(
                ($this->sortLpas)(
                    ($this->populateLpaMetadata)($lpaData, $userToken)
                )
            )
            : $lpaData;
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

        $lpaData = isset($lpaData) ? ($this->parseLpaData)($lpaData) : null;

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
            $lpaData = ($this->parseLpaData)($lpaData);

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
            $lpaData = ($this->parseLpaData)($lpaData);

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
     * @param string $referenceNumber
     * @param string $identity
     *
     * @return ArrayObject|null
     * @throws Exception
     */
    public function isLpaAlreadyAdded(string $referenceNumber, string $identity): ?ArrayObject
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
                return $lpasAdded[$userLpaActorToken];
            }
        }
        return null;
    }
}
