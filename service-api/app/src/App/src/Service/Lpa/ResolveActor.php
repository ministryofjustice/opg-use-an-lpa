<?php

declare(strict_types=1);

namespace App\Service\Lpa;

class ResolveActor
{
    /**
     * Given an LPA and an Actor ID, this returns the actor's details, and what type of actor they are.
     *
     * This function is used by code that expects to be able to check for Sirius uId's (code validation) and
     * database id's (UserActorLpa lookup) so it checks both fields for the id. This is not ideal but we now have
     * many thousands of live data rows with database id's at this point.
     *
     * @param array $lpa An LPA data structure
     * @param int $actorId The actors Database ID or Sirius UId to search for within the $lpa data structure
     * @return ?array A data structure containing details of the discovered actor
     */
    public function __invoke(array $lpa, int $actorId): ?array
    {
        // Determine if the actor is a primary attorney
        [$actor, $actorType] = $this->isAPrimaryAttorney($lpa, $actorId);

        // Is the actor a trust corporation
        if ($actor === null) {
            [$actor, $actorType] = $this->isATrustCorporation($lpa, $actorId);
        }

        // If not an attorney or tc, check if they're the donor.
        if ($actor === null) {
            [$actor, $actorType] = $this->isADonor($lpa, $actorId);
        }

        return $actor !== null
            ? [
                'type'    => $actorType,
                'details' => $actor,
            ]
            : null;
    }

    /**
     * @psalm-pure
     * @param array $lpa
     * @param int   $actorId
     * @return array{?array, ?string}
     */
    private function isATrustCorporation(
        array $lpa,
        int $actorId,
    ): array {
        $actor     = null;
        $actorType = null;

        if (isset($lpa['trustCorporations']) && is_array($lpa['trustCorporations'])) {
            foreach ($lpa['trustCorporations'] as $tc) {
                if ((int) $tc['id'] === $actorId || (int) $tc['uId'] === $actorId) {
                    $actor     = $tc;
                    $actorType = 'trust-corporation';
                }
            }
        }

        return [$actor, $actorType];
    }

    /**
     * @psalm-pure
     * @param array $lpa
     * @param int   $actorId
     * @return array{?array, ?string}
     */
    private function isAPrimaryAttorney(
        array $lpa,
        int $actorId,
    ): array {
        $actor     = null;
        $actorType = null;

        if (isset($lpa['attorneys']) && is_array($lpa['attorneys'])) {
            foreach ($lpa['attorneys'] as $attorney) {
                if ((int) $attorney['id'] === $actorId || (int) $attorney['uId'] === $actorId) {
                    $actor     = $attorney;
                    $actorType = 'primary-attorney';
                }
            }
        }

        return [$actor, $actorType];
    }

    /**
     * @psalm-pure
     * @param array $lpa
     * @param int   $actorId
     * @return array{?array, ?string}
     */
    private function isADonor(array $lpa, int $actorId): array
    {
        $actor     = null;
        $actorType = null;

        if (!isset($lpa['donor']) || !is_array($lpa['donor'])) {
            return [$actor, $actorType];
        }

        // TODO: [UML-2850] When new Sirius API has been released this property will always
        //       be present, then this `if` block can be removed.
        if (!isset($lpa['donor']['linked'])) {
            if ((int) $lpa['donor']['id'] === $actorId || (int) $lpa['donor']['uId'] === $actorId) {
                $actor     = $lpa['donor'];
                $actorType = 'donor';
            }
        } else {
            foreach ($lpa['donor']['linked'] as $value) {
                if ((int) $value['id'] === $actorId || (int) $value['uId'] === $actorId) {
                    $actor     = $lpa['donor'];
                    $actorType = 'donor';
                }
            }
        }

        return [$actor, $actorType];
    }
}
