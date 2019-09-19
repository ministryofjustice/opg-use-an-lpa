<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

interface ActorCodesInterface
{
    /**
     * Get an actor LPA code and actor details from the database.
     *
     * @param string $code
     * @return array
     */
    public function get(string $code) : ?array;

    /**
     * Marks a given actor code as used.
     * It will not be able to be used again.
     *
     * @param string $code
     */
    public function flagCodeAsUsed(string $code);
}
