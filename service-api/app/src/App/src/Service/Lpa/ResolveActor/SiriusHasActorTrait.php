<?php

declare(strict_types=1);

namespace App\Service\Lpa\ResolveActor;

/**
 * @psalm-require-implements HasActorInterface
 */
trait SiriusHasActorTrait
{
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

    private function isADonor(string $uid): ?LpaActor
    {
        foreach ($this->getDonor()['linked'] as $value) {
            if ((string) $value['id'] === $uid || $value['uId'] === $uid) {
                return new LpaActor($this->getDonor(), ActorType::DONOR);
            }
        }

        return null;
    }

    private function isAPrimaryAttorney(string $uid): ?LpaActor
    {
        foreach ($this->getAttorneys() as $attorney) {
            if ((string) $attorney['id'] === $uid || $attorney['uId'] === $uid) {
                return new LpaActor($attorney, ActorType::ATTORNEY);
            }
        }

        return null;
    }

    private function isATrustCorporation(string $uid): ?LpaActor
    {
        foreach ($this->getTrustCorporations() as $tc) {
            if ((string) $tc['id'] === $uid || $tc['uId'] === $uid) {
                return new LpaActor($tc, ActorType::TRUST_CORPORATION);
            }
        }

        return null;
    }

    abstract private function getAttorneys(): array;

    abstract private function getDonor(): array;

    abstract private function getTrustCorporations(): array;
}
