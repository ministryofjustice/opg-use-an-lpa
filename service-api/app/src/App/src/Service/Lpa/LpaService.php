<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository;
use App\Exception\GoneException;
use App\Service\ApiClient\ClientInterface;
use DateTime;
use Exception;

/**
 * Class LpaService
 * @package App\Service\Lpa
 */
class LpaService
{
    /**
     * @var Repository\ViewerCodesInterface
     */
    private $codesRepository;

    /**
     * @var Repository\ViewerCodeActivityInterface
     */
    private $activityRepository;

    /**
     * @var ClientInterface
     */
    private $apiClient;

    /**
     * LpaService constructor.
     * @param Repository\ViewerCodesInterface $codesRepository
     * @param Repository\ViewerCodeActivityInterface $activityRepository
     * @param ClientInterface $apiClient
     */
    public function __construct(
        Repository\ViewerCodesInterface $codesRepository,
        Repository\ViewerCodeActivityInterface $activityRepository,
        ClientInterface $apiClient
    )
    {
        $this->codesRepository = $codesRepository;
        $this->activityRepository = $activityRepository;
        $this->apiClient = $apiClient;
    }

    /**
     * Get an LPA using the ID value
     *
     * @param string $lpaId
     * @return array
     * @throws Exception
     */
    public function getById(string $lpaId) : array
    {
        $uri = sprintf('/lpas/%s', $lpaId);

        $lpa = $this->apiClient->httpGet($uri);

        return $lpa;
    }

    /**
     * Get an LPA using the share code
     *
     * @param string $shareCode
     * @return array
     * @throws Exception
     */
    public function getByCode(string $shareCode) : array
    {
        $viewerCodeData = $this->codesRepository->get($shareCode);

        if ($viewerCodeData['Expires'] < new DateTime()) {
            throw new GoneException('Share code expired');
        }

        //  Record the lookup in the activity table
        $this->activityRepository->recordSuccessfulLookupActivity($viewerCodeData['ViewerCode']);

        return $this->getById($viewerCodeData['SiriusId']);
    }
}
