<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\Repository\Response\LpaInterface;

interface LpasInterface
{
    /**
     * Looks up an LPA based on its Sirius uid.
     *
     * @param string $uid
     * @return Lpa
     */
    public function get(string $uid) : ?LpaInterface;

    /**
     * Looks up the all the LPA uids in the passed array.
     *
     * @param array $uids
     * @return array
     */
    public function lookup(array $uids) : array;    // array of Lpa objects.
}
