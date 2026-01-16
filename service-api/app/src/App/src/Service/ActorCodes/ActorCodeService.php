<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\{ActorCodeMarkAsUsedException, ActorCodeValidationException, ApiException};
use App\Service\Lpa\LpaManagerInterface;
use App\Service\Lpa\ResolveActor;
use App\Value\LpaUid;

class ActorCodeService
{
    public function __construct(
        private CodeValidationStrategyInterface $codeValidator,
        private UserLpaActorMapInterface $userLpaActorMapRepository,
        private LpaManagerInterface $lpaManager,
        private ResolveActor $resolveActor,
    ) {
    }

    /**
     * @throws ApiException
     */
    public function validateDetails(string $code, LpaUid $uid, string $dob): ?ValidatedActorCode
    {
        try {
            $actorCodeIsValid = $this->codeValidator->validateCode($code, $uid, $dob);

            $lpa     = $this->lpaManager->getByUid($uid);
            $actor   = ($this->resolveActor)($lpa->getData(), $actorCodeIsValid->actorUid);
            $lpaData = $lpa->getData();

            return new ValidatedActorCode($actor, $lpaData, $actorCodeIsValid->hasPaperVerificationCode ?? false);
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
        $details = $this->validateDetails($code, new LpaUid($uid), $dob);

        // If the details don't validate, stop here.
        if (is_null($details)) {
            return null;
        }

        $lpaId = $details->lpa->getUid();

        $lpas = $this->userLpaActorMapRepository->getByUserId($userId);

        /** @psalm-var array<array-key, string> $idToLpaMap */
        $idToLpaMap = array_column($lpas, 'Id', 'SiriusUid');

        if (array_key_exists($lpaId, $idToLpaMap)) {
            $id = $idToLpaMap[$lpaId];

            $this->userLpaActorMapRepository->activateRecord(
                $id,
                $details->actor->actor->getUid(),
                $code,
                $details->hasPaperVerificationCode,
            );
        } else {
            $id = $this->userLpaActorMapRepository->create(
                userId: $userId,
                siriusUid: $lpaId,
                actorId: (string) $details->actor->actor->getUid(),
                code: $code,
                hasPaperVerificationCode: $details->hasPaperVerificationCode,
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
