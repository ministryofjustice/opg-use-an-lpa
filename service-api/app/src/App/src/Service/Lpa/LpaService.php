<?php

namespace App\Service\Lpa;

use App\DataAccess\Repository;
use App\Exception\NotFoundException;
use App\Exception\GoneException;
use DateTime;

/**
 * Class LpaService
 * @package App\Service\Lpa
 */
class LpaService
{
    /**
     * @var Repository\ViewerCodesInterface
     */
    private $viewerCodesRepository;

    /**
     * @var Repository\ViewerCodeActivityInterface
     */
    private $viewerCodeActivityRepository;

    /**
     * @var Repository\LpasInterface
     */
    private $lpaRepository;

    /**
     * @var Repository\UserLpaActorMapInterface
     */
    private $userLpaActorMapRepository;

    public function __construct(
        Repository\ViewerCodesInterface $viewerCodesRepository,
        Repository\ViewerCodeActivityInterface $viewerCodeActivityRepository,
        Repository\LpasInterface $lpaRepository,
        Repository\UserLpaActorMapInterface $userLpaActorMapRepository
    )
    {
        $this->viewerCodesRepository = $viewerCodesRepository;
        $this->viewerCodeActivityRepository = $viewerCodeActivityRepository;
        $this->lpaRepository = $lpaRepository;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
    }

    /**
     * Get an LPA using the ID value
     *
     * @param string $uid
     * @return ?array
     */
    public function getByUid(string $uid) : ?array
    {
        return $this->lpaRepository->get($uid);
    }

    public function getAllForUser(string $userId) : array
    {
        // Returns an array of all the LPAs Ids (plus other metadata) in the user's account.
        $lpaActorMaps = $this->userLpaActorMapRepository->getUsersLpas($userId);

        $lpaUids = array_column($lpaActorMaps, 'SiriusUid');

        if (empty($lpaUids)) {
            return [];
        }

        // Return all the LPA details based on the Sirius Ids.
        $lpas = $this->lpaRepository->lookup($lpaUids);

        $result = [];

        // Map the results... #TODO: into an UserLpaActorObject.
        foreach($lpaActorMaps as $item) {
            $result[$item['Id']] = [
                'user-lpa-actor-token' => $item['Id'],
                'lpa' => (isset($lpas[$item['SiriusUid']])) ? $lpas[$item['SiriusUid']] : null,
                'actor' => 'details to add...',
            ];
        }

        return $result;
    }

    /**
     * Get an LPA using the share code
     *
     * @param string $shareCode
     * @return array
     * @throws \Exception
     */
    public function getByCode(string $shareCode) : array
    {
        $viewerCodeData = $this->viewerCodesRepository->get($shareCode);

        if ($viewerCodeData['Expires'] < new DateTime()) {
            throw new GoneException('Share code expired');
        }

        //  Record the lookup in the activity table
        $this->viewerCodeActivityRepository->recordSuccessfulLookupActivity($viewerCodeData['ViewerCode']);

        return $this->getById($viewerCodeData['SiriusUid']);
    }


}
