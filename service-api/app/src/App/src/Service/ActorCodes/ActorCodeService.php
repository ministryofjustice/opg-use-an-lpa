<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\DataAccess\Repository;
use App\Service\Lpa\LpaService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class ActorCodeService
{
    /**
     * @var Repository\ActorCodesInterface
     */
    private $actorCodesRepository;

    /**
     * @var Repository\UserLpaActorMapInterface
     */
    private $userLpaActorMapRepository;

    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ActorCodeService constructor.
     * @param Repository\ActorCodesInterface $viewerCodesRepository
     * @param Repository\UserLpaActorMapInterface $userLpaActorMapRepository
     * @param LpaService $lpaService
     * @param LoggerInterface $logger
     */
    public function __construct(
        Repository\ActorCodesInterface $viewerCodesRepository,
        Repository\UserLpaActorMapInterface $userLpaActorMapRepository,
        LpaService $lpaService,
        LoggerInterface $logger
    ) {
        $this->lpaService = $lpaService;
        $this->actorCodesRepository = $viewerCodesRepository;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
        $this->logger = $logger;
    }


    /**
     * @param string $code
     * @param string $uid
     * @param string $dob
     * @return array|null
     */
    public function validateDetails(string $code, string $uid, string $dob): ?array
    {
        $details = $this->actorCodesRepository->get($code);

        if (is_null($details)) {
            $this->logger->info('Validating code could not find details for code {code}', ['code' => $code]);
            return null;
        }

        if ($details['Active'] !== true) {
            $this->logger->info('Validating code {code} is inactive', ['code' => $code]);
            return null;
        }

        $lpa = $this->lpaService->getByUid($details['SiriusUid']);

        if (is_null($lpa)) {
            $this->logger->error('Validating code could not find LPA for SiriusUid {SiriusUid}', ['SiriusUid' => $details['SiriusUid']]);
            return null;
        }

        $actor = $this->lpaService->lookupActiveActorInLpa($lpa->getData(), $details['ActorLpaId']);

        if (is_null($actor)) {
            $this->logger->error('Validating code could not find actor {ActorLpaId} in LPA for SiriusUid {SiriusUid}',
                [
                   'ActorLpaId' => $details['ActorLpaId'],
                   'SiriusUid' => $details['SiriusUid'],
                ]
            );
            return null;
        }

        if ($code !== $details['ActorCode'] ) {
            $this->logger->info('Validating code {code} did not match {expected}',
                [
                    'code' => $code,
                    'expected' => $details['ActorCode'],
                ]
            );
            return null;
        }

        if ($uid !== $lpa->getData()['uId']) {
            $this->logger->info('Validating code uid {uid} did not match {expected}',
                [
                    'uid' => $uid,
                    'expected' => $lpa->getData()['uId'],
                ]
            );
            return null;
        }

        if ($dob !== $actor['details']['dob']) {
            $this->logger->info('Validating code dob {dob} did not match {expected}',
                [
                    'dob' => $dob,
                    'expected' => $actor['details']['dob'],
                ]
            );
            return null;
        }

        $lpaData = $lpa->getData();
        unset($lpaData['original_attorneys']);

        return [
            'actor' => $actor,
            'lpa' => $lpaData
        ];
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
            } catch (Repository\KeyCollisionException $e) {
                // Allows the loop to repeat with a new ID.
            }
        } while (!$added);

        //----

        try {
            $this->actorCodesRepository->flagCodeAsUsed($code);
        } catch (\Exception $e) {
            $this->userLpaActorMapRepository->delete($id);
        }

        return $id;
    }
}
