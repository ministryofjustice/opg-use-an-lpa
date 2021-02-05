<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;

class ResolveActor
{
    private const ACTIVE_ATTORNEY = 0;
    private const GHOST_ATTORNEY = 1;
    private const INACTIVE_ATTORNEY = 2;

    private LoggerInterface $logger;
    private GetAttorneyStatus $getAttorneyStatus;

    public function __construct(
        LoggerInterface $logger,
        GetAttorneyStatus $getAttorneyStatus
    ) {
        $this->logger = $logger;
        $this->getAttorneyStatus = $getAttorneyStatus;
    }

    /**
     * Given an LPA and an Actor ID, this returns the actor's details, and what type of actor they are.
     *
     * This function is used by code that expects to be able to check for Sirius uId's (code validation) and
     * database id's (UserActorLpa lookup) so it checks both fields for the id. This is not ideal but we now have
     * many thousands of live data rows with database id's at this point.
     *
     * TODO: Confirm if we need to look in Trust Corporations, or if an active Trust Corporation would appear
     *       in `attorneys`.
     *
     * @param array $lpa An LPA data structure
     * @param string $actorId The actors Database ID or Sirius UId to search for within the $lpa data structure
     * @return ?array A data structure containing details of the discovered actor
     */
    public function __invoke(array $lpa, string $actorId): ?array
    {
        $actor = null;
        $actorType = null;

        // Determine if the actor is a primary attorney
        if (isset($lpa['original_attorneys']) && is_array($lpa['original_attorneys'])) {
            foreach ($lpa['original_attorneys'] as $attorney) {
                if (
                    ((string)$attorney['id'] === $actorId || $attorney['uId'] === $actorId) &&
                    ($this->getAttorneyStatus)($attorney) === self::ACTIVE_ATTORNEY
                ) {
                    $actor = $attorney;
                    $actorType = 'primary-attorney';
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
}
