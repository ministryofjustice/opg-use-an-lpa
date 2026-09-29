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
     * Confirms adding a pre-validated LPA into a user's account.
     *
     * Transaction:
     *  1 - Add a mapping into our DB for the code
     *  2 - Mark the code as used
     *  3 - Undo 1 if 2 fails.
     *
     * @param ValidatedActorCode $details Pre-validated actor code details (must have been validated via validateDetails first)
     * @param string $code The activation code to mark as used
     * @param string $userId The user ID to associate with the LPA
     * @return string|null
     * @throws \Exception
     */
    public function confirmDetails(ValidatedActorCode $details, string $code, string $userId): ?string
    {
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
