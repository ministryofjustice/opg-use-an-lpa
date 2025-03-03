<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\Repository\Response\LpaInterface;
use App\Exception\ApiException;

interface LpasInterface
{
    /**
     * Looks up a LPA based on its uid.
     *
     * @param string $uid A LPA uid of the format M-XXXX-XXXX-XXXX or 7XXXXXXXXXXX
     * @return LpaInterface|null
     * @throws ApiException
     */
    public function get(string $uid): ?LpaInterface;

    /**
     * Looks up the all the LPA uids in the passed array.
     *
     * @param string[] $uids
     * @return LpaInterface[]
     * @throws ApiException
     */
    public function lookup(array $uids): array;
}
