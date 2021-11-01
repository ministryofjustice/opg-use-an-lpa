<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
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

        $lpaData = (!empty($lpaData) and ($lpaData['actor'] != null))  ? ($this->parseLpaData)($lpaData) : null;

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
                                'event_code' => EventCodes::VIEW_LPA_SHARE_CODE_CANCELLED,
                                'code' => $shareCode,
                                'type' => $trackRoute
                            ]
                        );
                    } else {
                        $this->logger->notice(
                            'Share code {code} expired when attempting to fetch {type}',
                            [
                                'event_code' => EventCodes::VIEW_LPA_SHARE_CODE_EXPIRED,
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
                            'event_code' => EventCodes::VIEW_LPA_SHARE_CODE_NOT_FOUND,
                            'type' => $trackRoute
                        ]
                    );
            }

            // still throw the exception up to the caller since handling of the issue will be done there
            throw $apiEx;
        }

        if (is_array($lpaData)) {
            $lpaData = ($this->parseLpaData)($lpaData);

            if ($trackRoute === 'summary') {
                // this getLpaByCode function is called to fetch the LPA for both the summary page and the
                // full lpa page, so we will just log the message once to avoid duplicate logs & incorrect stats
                $this->logger->notice(
                    'LPA found with Id {uId} retrieved by share code',
                    [
                        'event_code' => EventCodes::VIEW_LPA_SHARE_CODE_SUCCESS,
                        'uId' => ($lpaData->lpa)->getUId()
                    ]
                );
            }
        }

        return $lpaData;
    }
}
