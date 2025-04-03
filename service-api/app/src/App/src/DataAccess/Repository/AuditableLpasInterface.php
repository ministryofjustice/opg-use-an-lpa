<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

interface AuditableLpasInterface extends LpasInterface
{
    /**
     * Sets a unique piece of information on the repository that it can attach to outgoing requests
     * to provide an auditable request chain.
     *
     * Typically, any class that implements this will likely require it to be called before the usage
     * of the functionality provided by the LpasInterface.
     *
     * @param string $originatorId
     * @return AuditableLpasInterface
     */
    public function setOriginatorId(string $originatorId): AuditableLpasInterface;
}
