<?php

namespace App\Service\Lpa;

use RuntimeException;
use App\DataAccess\Repository;
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
    public function getByUid(string $uid) : ?Repository\Response\LpaInterface
    {
        return $this->lpaRepository->get($uid);
    }

    /**
     * Given a user token and a user id (who should own the token), return the actor and LPA details.
     *
     * @param string $token
     * @param string $userId
     * @return array|null
     */
    public function getByUserLpaActorToken(string $token, string $userId) : ?array
    {
        $map = $this->userLpaActorMapRepository->get($token);

        // Ensure the passed userId matches the passed token
        if ($userId !== $map['UserId']) {
            return null;
        }

        //---

        $lpa = $this->getByUid($map['SiriusUid']);

        if (is_null($lpa)) {
            return null;
        }

        //---

        return [
            'user-lpa-actor-token' => $map['Id'],
            'date' => $lpa->getLookupTime()->format('c'),
            'actor' => $actor = $this->lookupActorInLpa($lpa->getData(), $map['ActorId']),
            'lpa' => $lpa->getData(),
        ];
    }

    /**
     * Return all LPAs for the given user_id
     *
     * @param string $userId
     * @return array
     */
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

        // Map the results...
        foreach($lpaActorMaps as $item) {
            $lpa = $lpas[$item['SiriusUid']];

            $result[$item['Id']] = [
                'user-lpa-actor-token' => $item['Id'],
                'date' => $lpa->getLookupTime()->format('c'),
                'actor' => $actor = $this->lookupActorInLpa($lpa->getData(), $item['ActorId']),
                'lpa' => $lpa->getData(),
            ];
        }

        return $result;
    }

    /**
     * Get an LPA using the share code.
     * 
     * @param string $viewerCode
     * @param string $donorSurname
     * @param bool $logActivity
     * @return array|null
     * @throws \Exception
     */
    public function getByViewerCode(string $viewerCode, string $donorSurname, bool $logActivity) : ?array
    {
        $viewerCodeData = $this->viewerCodesRepository->get($viewerCode);

        if (is_null($viewerCodeData)) {
            return null;
        }

        $lpa = $this->getByUid($viewerCodeData['SiriusUid']);

        //---

        // Check donor's surname

        if (is_null($lpa)
            || !isset($lpa->getData()['donor']['surname'])
            || strtolower($lpa->getData()['donor']['surname']) !== strtolower($donorSurname)
        ){
            return null;
        }

        //---

        // Whilst the checks in this section could be done before we lookup the LPA, they are done
        // at this point as we only want to acknowledge if a code has expired iff donor surname matched.

        if (!isset($viewerCodeData['Expires']) || !($viewerCodeData['Expires'] instanceof DateTime)) {
            throw new RuntimeException("'Expires' filed missing or invalid.");
        }

        if (new DateTime() > $viewerCodeData['Expires']) {
            throw new GoneException('Share code expired');
        }

        //---

        if ($logActivity) {
            // Record the lookup in the activity table
            // We only do this if it was a 'full' lookup. i.e. not just the confirmation page.
            $this->viewerCodeActivityRepository->recordSuccessfulLookupActivity($viewerCodeData['ViewerCode']);
        }

        return [
            'date' => $lpa->getLookupTime()->format('c'),
            'expires' => $viewerCodeData['Expires']->format('c'),
            'organisation' => $viewerCodeData['Organisation'],
            'lpa' => $lpa->getData(),
        ];
    }


    /**
     * Given an LPA and an Actor ID, this returns the actor's details, and what type of actor they are.
     *
     * TODO: Confirm if we need to look in Trust Corporations, or if an active Trust Corporation would appear in `attorneys`.
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
