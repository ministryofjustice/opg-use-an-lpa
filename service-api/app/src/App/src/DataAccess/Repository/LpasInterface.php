<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\Repository\Response\LpaInterface;
use App\Exception\ApiException;
use GuzzleHttp\Promise\PromiseInterface;

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
     * @psalm-param list<string> $uids
     * @return LpaInterface[]
     * @throws ApiException
     */
    public function lookup(array $uids): array;

    /**
     * Starts looking up all the LPA uids in the passed array, returning a promise that fulfils with the same
     * array that {@see LpasInterface::lookup()} returns.
     *
     * The optional callback is invoked for each LPA as soon as it is available. If it returns a promise, that
     * promise must settle before the returned promise is fulfilled, allowing additional work for each LPA
     * to happen whilst other LPAs are still being fetched.
     *
     * @param string[] $uids
     * @psalm-param list<string> $uids
     * @param (callable(LpaInterface, string): (PromiseInterface|null))|null $onLpa Called with each LPA and its uid
     * @return PromiseInterface Rejects with an ApiException on failure
     */
    public function lookupAsync(array $uids, ?callable $onLpa = null): PromiseInterface;
}
