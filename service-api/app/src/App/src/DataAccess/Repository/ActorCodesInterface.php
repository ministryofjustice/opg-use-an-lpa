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
}
