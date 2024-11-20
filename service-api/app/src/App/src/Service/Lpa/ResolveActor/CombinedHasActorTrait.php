<?php

declare(strict_types=1);

namespace App\Service\Lpa\ResolveActor;

use App\Entity\Sirius\SiriusLpaDonor;

/**
 * @psalm-require-implements HasActorInterface
 */
trait CombinedHasActorTrait
{
    public function hasActor(string $uid): ?LpaActor
    {
        // Determine if the actor is a primary attorney
        $actor = $this->isAPrimaryAttorney($uid);

        // If not an attorney, check if they're the donor.
        if ($actor === null) {
            $actor = $this->isADonor($uid);
        }

        // Is the actor a trust corporation
        if ($actor === null) {
            $actor = $this->isATrustCorporation($uid);
        }

        return $actor;
    }

    private function isADonor(string $uid): ?LpaActor
    {
        $donor = $this->getDonor();

        if ($donor instanceof SiriusLpaDonor) {
            foreach ($donor->linked as $linkedDonor) {
                if ($linkedDonor['uId'] === $uid || (string) $linkedDonor['id'] === $uid) {
                    return new LpaActor($this->getDonor(), ActorType::DONOR);
                }
            }
        } elseif ($donor->getUid() === $uid || $donor->getId() === $uid) {
            return new LpaActor($donor, ActorType::DONOR);
        }

        return null;
    }

    private function isAPrimaryAttorney(string $uid): ?LpaActor
    {
        foreach ($this->getAttorneys() as $attorney) {
            if ($attorney->getUid() === $uid || $attorney->getId() === $uid) {
                return new LpaActor($attorney, ActorType::ATTORNEY);
            }
        }

        return null;
    }

    private function isATrustCorporation(string $uid): ?LpaActor
    {
        foreach ($this->getTrustCorporations() as $tc) {
            if ($tc->getUid() === $uid || $tc->getId() === $uid) {
                return new LpaActor($tc, ActorType::TRUST_CORPORATION);
            }
        }

        return null;
    }
}
