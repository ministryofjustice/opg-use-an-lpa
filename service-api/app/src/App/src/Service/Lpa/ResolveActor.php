<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\ResolveActor\LpaActor;

class ResolveActor
{
    /**
     * Given an LPA and an Actor ID, this returns the actor's details, and what type of actor they are.
     *
     * This function is used by code that expects to be able to check for Sirius uId's (code validation) and
     * database id's (UserActorLpa lookup) so it checks both fields for the id. This is not ideal but we now have
     * many thousands of live data rows with database id's at this point.
     *
     * @param HasActorInterface $lpa An LPA data structure that contains actors.
     * @param string $uid The actors Database ID, Sirius Uid or LpaStore Uid to search for within the
     *                    $lpa data structure.
     * @return ?LpaActor A data structure containing details of the discovered actor
     */
    public function __invoke(HasActorInterface $lpa, string $uid): ?LpaActor
    {
        return $lpa->hasActor($uid);
    }
}
