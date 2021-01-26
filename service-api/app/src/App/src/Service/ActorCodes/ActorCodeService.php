<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\DataAccess\{ApiGateway\ActorCodes,
    Repository\ActorCodesInterface,
    Repository\KeyCollisionException,
    Repository\UserLpaActorMapInterface};
use App\Exception\{ActorCodeMarkAsUsedException, ActorCodeValidationException};
use App\Service\Lpa\LpaService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class ActorCodeService
{
    private CodeValidationStrategyInterface $codeValidator;

    private UserLpaActorMapInterface $userLpaActorMapRepository;

    private LpaService $lpaService;

    private LoggerInterface $logger;

    private ActorCodes $actorCodes;

    /**
     * ActorCodeService constructor.
     *
     * @param CodeValidationStrategyInterface $codeValidator
     * @param UserLpaActorMapInterface $userLpaActorMapRepository
     * @param LpaService $lpaService
     * @param LoggerInterface $logger
     * @param ActorCodes $actorCodes
     */
    public function __construct(
        CodeValidationStrategyInterface $codeValidator,
        UserLpaActorMapInterface $userLpaActorMapRepository,
        LpaService $lpaService,
        LoggerInterface $logger,
        ActorCodes $actorCodes
    ) {
        $this->codeValidator = $codeValidator;
        $this->lpaService = $lpaService;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
        $this->logger = $logger;
        $this->actorCodes = $actorCodes;
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

            $actor = $this->lpaService->lookupActiveActorInLpa($lpa->getData(), $actorUid);

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
     * @param string $actorId
     * @return array|null
     * @throws \Exception
     */
    public function confirmDetails(string $code, string $uid, string $dob, string $actorId): ?string
    {
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
                    $actorId,
                    $details['lpa']['uId'],
                    $details['actor']['details']['id']
                );

                $added = true;
            } catch (KeyCollisionException $e) {
                // Allows the loop to repeat with a new ID.
            }
        } while (!$added);

        //----

        try {
            $this->codeValidator->flagCodeAsUsed($code);
        } catch (ActorCodeMarkAsUsedException $e) {
            $this->userLpaActorMapRepository->delete($id);
        }

        return $id;
    }

    /**
     * Checks if an actor already has an active activation key
     *
     * @param string $lpaId
     * @param string $actorId
     * @return bool
     */
    public function hasActivationCode(string $lpaId, string $actorId): bool
    {
        $response = $this->actorCodes->checkActorHasCode($lpaId, $actorId);

        if (is_null($response->getData()['Created'])) {
            return false;
        }

        $this->logger->notice(
            'Activation key request denied for actor {actorId} on LPA {lpaId} as they have an active activation key',
            [
                'actorId' => $actorId,
                'lpaId'   => $lpaId
            ]
        );

        return true;
    }
}
