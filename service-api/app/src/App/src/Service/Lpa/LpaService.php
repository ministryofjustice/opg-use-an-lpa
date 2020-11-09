<?php

namespace App\Service\Lpa;

use App\DataAccess\Repository;
use App\Exception\GoneException;
use DateTime;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class LpaService
 * @package App\Service\Lpa
 */
class LpaService
{
    private const ACTIVE_ATTORNEY = 0;
    private const GHOST_ATTORNEY = 1;
    private const INACTIVE_ATTORNEY = 2;

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

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Repository\ViewerCodesInterface $viewerCodesRepository,
        Repository\ViewerCodeActivityInterface $viewerCodeActivityRepository,
        Repository\LpasInterface $lpaRepository,
        Repository\UserLpaActorMapInterface $userLpaActorMapRepository,
        LoggerInterface $logger
    ) {
        $this->viewerCodesRepository = $viewerCodesRepository;
        $this->viewerCodeActivityRepository = $viewerCodeActivityRepository;
        $this->lpaRepository = $lpaRepository;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
        $this->logger = $logger;
    }

    /**
     * Get an LPA using the ID value
     *
     * @param string $uid
     * @return ?array
     */
    public function getByUid(string $uid): ?Repository\Response\LpaInterface
    {
        $lpa = $this->lpaRepository->get($uid);
        if ($lpa === null) {
            return null;
        }

        $lpaData = $lpa->getData();

        if ($lpaData['attorneys'] !== null) {
            $lpaData['original_attorneys'] = $lpaData['attorneys'];
            $lpaData['attorneys'] = array_values(array_filter($lpaData['attorneys'], function ($attorney) {
                return $this->attorneyStatus($attorney) === self::ACTIVE_ATTORNEY;
            }));
        }

        return new Repository\Response\Lpa($lpaData, $lpa->getLookupTime());
    }

    /**
     * Given a user token and a user id (who should own the token), return the actor and LPA details.
     *
     * @param string $token
     * @param string $userId
     * @return array|null
     */
    public function getByUserLpaActorToken(string $token, string $userId): ?array
    {
        $map = $this->userLpaActorMapRepository->get($token);

        // Ensure the passed userId matches the passed token
        if ($userId !== $map['UserId']) {
            return null;
        }

        $lpa = $this->getByUid($map['SiriusUid']);

        if (is_null($lpa)) {
            return null;
        }

        $lpaData = $lpa->getData();
        $actor = $this->lookupActiveActorInLpa($lpaData, $map['ActorId']);
        unset($lpaData['original_attorneys']);

        // If an active attorney is not found then we should not return an lpa
        if (is_null($actor)) {
            return null;
        }

        return [
            'user-lpa-actor-token' => $map['Id'],
            'date' => $lpa->getLookupTime()->format('c'),
            'actor' => $actor,
            'lpa' => $lpaData,
        ];
    }

    /**
     * Return all LPAs for the given user_id
     *
     * @param string $userId
     * @return array
     */
    public function getAllForUser(string $userId): array
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
        foreach ($lpaActorMaps as $item) {
            $lpa = $lpas[$item['SiriusUid']];
            $lpaData = $lpa->getData();
            $actor = $this->lookupActiveActorInLpa($lpaData, $item['ActorId']);
            $added = $item['Added']->format('Y-m-d H:i:s');
            unset($lpaData['original_attorneys']);

            $result[$item['Id']] = [
                'user-lpa-actor-token' => $item['Id'],
                'date' => $lpa->getLookupTime()->format('c'),
                'actor' => $actor,
                'lpa' => $lpaData,
                'added' => $added
            ];
        }

        return $result;
    }

    /**
     * Get an LPA using the share code.
     *
     * @param string $viewerCode
     * @param string $donorSurname
     * @param string|null $organisation
     * @return array|null
     */
    public function getByViewerCode(string $viewerCode, string $donorSurname, ?string $organisation = null): ?array
    {
        $viewerCodeData = $this->viewerCodesRepository->get($viewerCode);

        if (is_null($viewerCodeData)) {
            $this->logger->info('The code entered by user to view LPA is not found in the database.');
            return null;
        }

        $lpa = $this->getByUid($viewerCodeData['SiriusUid']);

        //---

        // Check donor's surname

        if (
            is_null($lpa)
            || !isset($lpa->getData()['donor']['surname'])
            || strtolower($lpa->getData()['donor']['surname']) !== strtolower($donorSurname)
        ) {
            return null;
        }

        //---
        // Whilst the checks in this section could be done before we lookup the LPA, they are done
        // at this point as we only want to acknowledge if a code has expired iff donor surname matched.

        if (!isset($viewerCodeData['Expires']) || !($viewerCodeData['Expires'] instanceof DateTime)) {
            $this->logger->info('The code {code} entered by user to view LPA does not have an expiry date set.', ['code' => $viewerCode]);
            throw new RuntimeException("'Expires' field missing or invalid.");
        }

        if (new DateTime() > $viewerCodeData['Expires']) {
            $this->logger->info('The code {code} entered by user to view LPA has expired.', ['code' => $viewerCode]);
            throw new GoneException('Share code expired');
        }

        if (isset($viewerCodeData['Cancelled'])) {
            $this->logger->info('The code {code} entered by user is cancelled.', ['code' => $viewerCode]);
            throw new GoneException('Share code cancelled');
        }

        if (!is_null($organisation)) {
            // Record the lookup in the activity table
            // We only do this if the organisation is provided
            $this->viewerCodeActivityRepository->recordSuccessfulLookupActivity($viewerCodeData['ViewerCode'], $organisation);
        }

        $lpaData = $lpa->getData();
        unset($lpaData['original_attorneys']);

        $lpaData = [
            'date'         => $lpa->getLookupTime()->format('c'),
            'expires'      => $viewerCodeData['Expires']->format('c'),
            'organisation' => $viewerCodeData['Organisation'],
            'lpa'          => $lpaData,
        ];

        if (isset($viewerCodeData['Cancelled'])) {
            $lpaData['cancelled'] = $viewerCodeData['Cancelled']->format('c');
        }

        return $lpaData;
    }


    /**
     * Given an LPA and an Actor ID, this returns the actor's details, and what type of actor they are.
     *
     * TODO: Confirm if we need to look in Trust Corporations, or if an active Trust Corporation would appear in `attorneys`.
     * TODO: Remove dual checks for id/uId when code validation API goes live
     *
     * @param array $lpa
     * @param string $actorId
     * @return array|null
     */
    public function lookupActiveActorInLpa(array $lpa, string $actorId): ?array
    {
        $actor = null;
        $actorType = null;

        // Determine if the actor is a primary attorney
        if (isset($lpa['original_attorneys']) && is_array($lpa['original_attorneys'])) {
            foreach ($lpa['original_attorneys'] as $attorney) {
                if ((string)$attorney['id'] === $actorId || $attorney['uId'] === $actorId) {
                    switch ($this->attorneyStatus($attorney)) {
                        case self::ACTIVE_ATTORNEY:
                            $actor = $attorney;
                            $actorType = 'primary-attorney';
                            break;

                        case self::GHOST_ATTORNEY:
                            $this->logger->info('Looked up attorney {id} but is a ghost', ['id' => $attorney['id']]);
                            break;

                        case self::INACTIVE_ATTORNEY:
                            $this->logger->info('Looked up attorney {id} but is inactive', ['id' => $attorney['id']]);
                            break;
                    }
                }
            }
        } elseif (isset($lpa['attorneys']) && is_array($lpa['attorneys'])) {
            foreach ($lpa['attorneys'] as $attorney) {
                if ((string)$attorney['id'] === $actorId || $attorney['uId'] === $actorId) {
                    $actor = $attorney;
                    $actorType = 'primary-attorney';
                }
            }
        }

        // If not an attorney, check if they're the donor.
        if (is_null($actor) && $this->isDonor($lpa, $actorId)) {
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

    private function isDonor(array $lpa, string $actorId): bool
    {
        if (!isset($lpa['donor']) || !is_array($lpa['donor'])) {
            return false;
        }

        // TODO: When new Sirius API has been released this property will always
        //       be present, then this `if` block can be removed.
        if (!isset($lpa['donor']['linked'])) {
            return ((string)$lpa['donor']['id'] === $actorId || $lpa['donor']['uId'] === $actorId);
        }

        foreach ($lpa['donor']['linked'] as $key => $value) {
            if ((string)$value['id'] === $actorId || $value['uId'] === $actorId) {
                return true;
            }
        }

        return false;
    }

    private function attorneyStatus(array $attorney): int
    {
        if (empty($attorney['firstname']) && empty($attorney['surname'])) {
            return self::GHOST_ATTORNEY;
        }

        if (!$attorney['systemStatus']) {
            return self::INACTIVE_ATTORNEY;
        }

        return self::ACTIVE_ATTORNEY;
    }

    /**
     * @param string $accountId
     * @return array
     */
    public function deleteUserAccount(string $accountId): array
    {
        $user = $this->usersRepository->get($accountId);

        if (is_null($user)) {
            $this->logger->notice(
                'Account not found for user Id {Id}',
                ['Id' => $accountId]
            );
            throw new NotFoundException('User not found for account with Id ' . $accountId);
        }

        return $this->usersRepository->delete($accountId);
    }

    /**
     * @param string $actorLpaToken
     * @return array
     */
    public function removerLPaFromUserLpaActorMap(string $actorLpaToken): array
    {
        $userActorLpa = $this->userLpaActorMapRepository->get($actorLpaToken);

        if (is_null($userActorLpa)) {
            $this->logger->notice(
                'User actor lpa record  not found for actor token {Id}',
                ['Id' => $actorLpaToken]
            );
            throw new NotFoundException('User actor lpa record  not found for actor token ' . $actorLpaToken);
        }

        //lookup lpactortoken in viewer code table and remove actor association with viewer code

        return $this->userLpaActorMapRepository->delete($actorLpaToken);
    }

}
