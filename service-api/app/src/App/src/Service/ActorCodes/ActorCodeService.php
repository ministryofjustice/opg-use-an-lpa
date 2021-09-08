<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\DataAccess\{Repository\KeyCollisionException, Repository\UserLpaActorMapInterface};
use App\Exception\{ActorCodeMarkAsUsedException, ActorCodeValidationException};
use App\Service\Lpa\LpaService;
use App\Service\Lpa\ResolveActor;
use Ramsey\Uuid\Uuid;

class ActorCodeService
{
    private CodeValidationStrategyInterface $codeValidator;

    private UserLpaActorMapInterface $userLpaActorMapRepository;

    private LpaService $lpaService;

    private ResolveActor $resolveActor;

    /**
     * ActorCodeService constructor.
     *
     * @param CodeValidationStrategyInterface $codeValidator
     * @param UserLpaActorMapInterface $userLpaActorMapRepository
     * @param LpaService $lpaService
     * @param ResolveActor $resolveActor
     */
    public function __construct(
        CodeValidationStrategyInterface $codeValidator,
        UserLpaActorMapInterface $userLpaActorMapRepository,
        LpaService $lpaService,
        ResolveActor $resolveActor
    ) {
        $this->codeValidator = $codeValidator;
        $this->lpaService = $lpaService;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
        $this->resolveActor = $resolveActor;
    }

    /**
     * @param string $userId
     * @param array  $details
     *
     * @return string
     * @throws \Exception
     */
    public function addLpaRecord(string $userId, array $details): string
    {
        do {
            $added = false;

            $id = Uuid::uuid4()->toString();

            try {
                $this->userLpaActorMapRepository->create(
                    $id,
                    $userId,
                    $details['lpa']['uId'],
                    (string)$details['actor']['details']['id']
                );

                $added = true;
            } catch (KeyCollisionException $e) {
                // Allows the loop to repeat with a new ID.
            }
        } while (!$added);
        return $id;
    }

    /**
     * Removes TTL from entry that is already inside the database
     *
     * @param string $userLpaActorMapId the database id to remove the TTL from
     *
     * @return string|null returns the database ID of the LPA that has had it's TTL removed
     */
    private function removeTTLFromRequest(string $userLpaActorMapId): ?string
    {
        $this->userLpaActorMapRepository->removeActivateBy($userLpaActorMapId);

        return $userLpaActorMapId;
    }

    /**
     * @param string $code
     * @param string $uid
     * @param string $dob
     * @return array|null
     */
    public function validateDetails(string $code, string $uid, string $dob): ?array
    {
        try {
            $actorUid = $this->codeValidator->validateCode($code, $uid, $dob);

            $lpa = $this->lpaService->getByUid($uid);

            $actor = ($this->resolveActor)($lpa->getData(), $actorUid);

            $lpaData = $lpa->getData();
            unset($lpaData['original_attorneys']);

            return [
                'actor' => $actor,
                'lpa' => $lpaData
            ];
        } catch (ActorCodeValidationException $acve) {
            return null;
        }
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
     * @return string|null
     * @throws \Exception
     */
    public function confirmDetails(string $code, string $uid, string $dob, string $userId): ?string
    {
        $details = $this->validateDetails($code, $uid, $dob);

        // If the details don't validate, stop here.
        if (is_null($details)) {
            return null;
        }

        //---
        $lpaId = $details['lpa']['uId'];

        $lpas = $this->userLpaActorMapRepository->getUsersLpas($userId);
        $idToLpaMap = array_column($lpas, 'Id', 'SiriusUid');

        if (array_key_exists($lpaId, $idToLpaMap)) {
            $id = $this->removeTTLFromRequest($idToLpaMap[$lpaId]);
        } else {
            $id = $this->addLpaRecord($userId, $details);
        }

        try {
            $this->codeValidator->flagCodeAsUsed($code);
        } catch (ActorCodeMarkAsUsedException $e) {
            $this->userLpaActorMapRepository->delete($id);
        }

        return $id;
    }
}
