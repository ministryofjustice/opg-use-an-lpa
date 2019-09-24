<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\DataAccess\Repository;
use App\DataModel\UserLpaActor;
use App\Service\Lpa\LpaService;
use Ramsey\Uuid\Uuid;

class ActorCodeService
{
    /**
     * @var Repository\ActorCodesInterface
     */
    private $actorCodesRepository;

    private $userLpaActorMapRepository;

    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * ActorCodeService constructor.
     * @param Repository\ActorCodesInterface $viewerCodesRepository
     * @param Repository\UserLpaActorMapInterface $userLpaActorMapRepository
     * @param LpaService $lpaService
     */
    public function __construct(
        Repository\ActorCodesInterface $viewerCodesRepository,
        Repository\UserLpaActorMapInterface $userLpaActorMapRepository,
        LpaService $lpaService
    )
    {
        $this->lpaService = $lpaService;
        $this->actorCodesRepository = $viewerCodesRepository;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
    }

    /**
     * Confirms adding an LPA into a user's account.
     *
     * Transaction:
     *  1 - Validate the code's details
     *  2 - Add a mapping into our DB for the code
     *  3 - Mark the code as used
     *  4 - Undo 2 if 3 fails.
     *
     * @param string $code
     * @param string $uid
     * @param string $dob
     * @param string $userId
     * @return array|null
     * @throws \Exception
     */
    public function confirmDetails(string $code, string $uid, string $dob, string $userId) : ?string {

        $details = $this->validateDetails($code, $uid, $dob);

        // If the details don't validate, stop here.
        if (is_null($details)) {
            return null;
        }

        //---

        $id = null;

        do {
            $added = false;

            $id = Uuid::uuid4()->toString();

            try {
                $this->userLpaActorMapRepository->create(
                    $id,
                    $userId,
                    $details['lpa']['uId'],
                    $details['actor']['details']['id']
                );

                $added = true;
            } catch (Repository\KeyCollisionException $e) {
                // Allows the loop to repeat with a new ID.
            }

        } while(!$added);

        //----

        try {
            $this->actorCodesRepository->flagCodeAsUsed($code);
        } catch (\Exception $e){
            $this->userLpaActorMapRepository->delete($id);
        }

        return $id;
    }

    /**
     * @param string $code
     * @param string $uid
     * @param string $dob
     * @return array|null
     */
    public function validateDetails(string $code, string $uid, string $dob) : ?array
    {
        //-----------------------
        // Lookup the LPA and the Actor's LPA ID.

        $details = $this->actorCodesRepository->get($code);

        if (is_null($details)) {
            return null;
        }

        //-----------------------
        // Ensure the code is active

        if ($details['Active'] !== true) {
            return null;
        }

        //-----------------------
        // Lookup the full LPA

        $lpa = $this->lpaService->getByUid($details['SiriusUid']);

        if (is_null($lpa)) {
            return null;
        }

        //----------------------
        // Find the actor in the LPA

        $actor = $this->lpaService->lookupActorInLpa($lpa->getData(), $details['ActorLpaId']);

        if (is_null($actor)) {
            return null;
        }

        //----------------------
        // Validate the details match

        if ($code != $details['ActorCode'] || $uid != $lpa->getData()['uId'] || $dob != $actor['details']['dob']) {
            return null;
        }

        //---

        return [
            'actor' => $actor,
            'lpa' => $lpa->getData(),
        ];

    }

}
