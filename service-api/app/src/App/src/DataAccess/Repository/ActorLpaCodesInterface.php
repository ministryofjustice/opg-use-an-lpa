<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

interface ActorLpaCodesInterface
{
    /**
     * Get an actor LPA code from the database
     *
     * @param string $code
     * @return array
     */
    public function get(string $code) : array;
}
