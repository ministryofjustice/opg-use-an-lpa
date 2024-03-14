<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\{ActorCodeMarkAsUsedException, ActorCodeValidationException};
use App\Service\Lpa\LpaService;
use App\Service\Lpa\ResolveActor;

class ActorCodeService
{
    public function __construct(
        private CodeValidationStrategyInterface $codeValidator,
        private UserLpaActorMapInterface $userLpaActorMapRepository,
        private LpaService $lpaService,
        private ResolveActor $resolveActor,
    ) {
    }

    /**
     * Removes TTL from entry that is already inside the database
     *
     * @param string $userLpaActorMapId the database id to remove the TTL from
     * @return string returns the database ID of the LPA that has had it's TTL removed
     */
    private function activateRecord(string $userLpaActorMapId, string $actorId, string $code): string
    {
        $this->userLpaActorMapRepository->activateRecord($userLpaActorMapId, $actorId, $code);

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

            $actor = ($this->resolveActor)($lpa->getData(), (int) $actorUid);

            $lpaData = $lpa->getData();
            unset($lpaData['original_attorneys']);

            return [
                'actor' => $actor,
                'lpa'   => $lpaData,
            ];
        } catch (ActorCodeValidationException) {
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

        $lpas       = $this->userLpaActorMapRepository->getByUserId($userId);
        $idToLpaMap = array_column($lpas, 'Id', 'SiriusUid');

        if (array_key_exists($lpaId, $idToLpaMap)) {
            $id = $this->activateRecord($idToLpaMap[$lpaId], $details['actor']['details']['uId'], $code);
        } else {
            $id = $this->userLpaActorMapRepository->create(
                $userId,
                $lpaId,
                (string)$details['actor']['details']['uId'],
                null,
                null,
                $code
            );
        }

        try {
            $this->codeValidator->flagCodeAsUsed($code);
        } catch (ActorCodeMarkAsUsedException) {
            $this->userLpaActorMapRepository->delete($id);
        }

        return $id;
    }
}
