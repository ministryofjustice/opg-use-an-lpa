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

    public function getByUserLpaActorToken(string $token, ?string $userId = null) : ?array
    {
        $map = $this->userLpaActorMapRepository->get($token);

        // If a userId was passed, ensure that is matches the passed token
        if (!is_null($userId) && $userId !== $map['UserId']) {
            return null;
        }

        //---

        $lpa = $this->getByUid($map['SiriusUid']);

        if (empty($lpa)) {
            return null;
        }

        return [
            'user-lpa-actor-token' => $map['Id'],
            'actor' => $actor = $this->lookupActorInLpa($lpa, $map['ActorId']),
            'lpa' => $lpa,
        ];
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

        // Map the results... #TODO: into an UserLpaActorObject?
        foreach($lpaActorMaps as $item) {
            $result[$item['Id']] = [
                'user-lpa-actor-token' => $item['Id'],
                'actor' => $actor = $this->lookupActorInLpa($lpas[$item['SiriusUid']], $item['ActorId']),
                'lpa' => $lpas[$item['SiriusUid']],
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


    /**
     * Given an LPA and an Actor ID, this returns the actor's details, and what type of actor they are.
     *
     * @param array $lpa
     * @param int $actorId
     * @return array|null
     */
    public function lookupActorInLpa(array $lpa, int $actorId) : ?array
    {
        $actor = null;
        $actorType = null;

        // Determine if the actor is a primary attorney
        if (isset($lpa['attorneys']) && is_array($lpa['attorneys'])) {
            foreach ($lpa['attorneys'] as $attorney) {
                if ($attorney['id'] == $actorId) {
                    $actor = $attorney;
                    $actorType = 'primary-attorney';
                    break;
                }
            }
        }

        // If no an attorney, check if they're the donor.
        if (is_null($actor) &&
            isset($lpa['donor']) &&
            is_array($lpa['donor']) &&
            $lpa['donor']['id'] == $actorId)
        {
            $actor = $lpa['donor'];
            $actorType = 'donor';
        }

        if (is_null($actor)) {
            return null;
        }

        return [
            'type' => $actorType,
            'details' => $actor,
        ];
    }
}
