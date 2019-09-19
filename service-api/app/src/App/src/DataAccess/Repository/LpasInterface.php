<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

interface LpasInterface
{
    /**
     * Looks up an LPA based on its Sirius uid.
     *
     * @param string $uid
     * @return array
     */
    public function get(string $uid) : ?array;

    /**
     * Looks up the all the LPA uids in the passed array.
     *
     * @param array $uids
     * @return array
     */
    public function lookup(array $uids) : array;
}
