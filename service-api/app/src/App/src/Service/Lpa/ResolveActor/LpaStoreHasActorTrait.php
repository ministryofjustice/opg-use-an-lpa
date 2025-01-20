<?php

declare(strict_types=1);

namespace App\Service\Lpa\ResolveActor;

use App\Entity\LpaStore\LpaStoreAttorney;
use App\Entity\LpaStore\LpaStoreDonor;
use App\Entity\LpaStore\LpaStoreTrustCorporation;

/**
 * @psalm-require-implements HasActorInterface
 */
trait LpaStoreHasActorTrait
{
    /** @var LpaStoreAttorney[] */
    public readonly ?array $attorneys;

    /** @psalm-var ?LpaStoreDonor  */
    public readonly ?object $donor;

    /** @var LpaStoreTrustCorporation[] */
    public readonly ?array $trustCorporations;

    public function hasActor(string $uid): ?LpaActor
    {
        // Determine if the actor is a primary attorney
        $actor = $this->isAPrimaryAttorney($uid);

        // If not an attorney or tc, check if they're the donor.
        if ($actor === null) {
            $actor = $this->isADonor($uid);
        }

        // Is the actor a trust corporation
        if ($actor === null) {
            $actor = $this->isATrustCorporation($uid);
        }

        return $actor;
    }

    private function isAPrimaryAttorney(string $uid): ?LpaActor
    {
        foreach ($this->attorneys as $attorney) {
            if ((string) $attorney->uId === $uid) {
                return new LpaActor($attorney, ActorType::ATTORNEY);
            }
        }

        return null;
    }

    private function isADonor(string $uid): ?LpaActor
    {
        if ($this->donor->uId === $uid) {
            return new LpaActor($this->donor, ActorType::DONOR);
        }

        return null;
    }

    private function isATrustCorporation(string $uid): ?LpaActor
    {
        foreach ($this->trustCorporations as $tc) {
            if ($tc->uId === $uid) {
                return new LpaActor($tc, ActorType::TRUST_CORPORATION);
            }
        }

        return null;
    }
}
