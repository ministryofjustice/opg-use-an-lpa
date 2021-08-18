<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

/**
 * Represents access to mappings between a User Account, an LPA, and the associated Actor on the LPA.
 *
 * Interface UserLpaActorMapInterface
 * @package App\DataAccess\Repository
 */
interface UserLpaActorMapInterface
{
    /**
     * Creates a new mapping in the DB
     *
     * @param string $lpaActorToken
     * @param string $userId
     * @param string $siriusUid
     * @param string $actorId The Sirius formated UID that is associated with an actor in the system
     * @param string|null $expiryInterval
     *
     * @throws KeyCollisionException
     */
    public function create(
        string $lpaActorToken,
        string $userId,
        string $siriusUid,
        string $actorId,
        string $expiryInterval = null
    );

    /**
     * Returns the IDs for the LPA and associated Actor for the given token.
     *
     * @param string $lpaActorToken
     * @return mixed
     */
    public function get(string $lpaActorToken): ?array;

    /**
     * Returns LPA uids for the given user_id.
     *
     * @param $userId
     * @return mixed
     */
    public function getUsersLpas(string $userId): ?array;

    /**
     * Deletes an relation. Should only be called if a rollback is needed.
     *
     * @param string $lpaActorToken
     * @return mixed
     */
    public function delete(string $lpaActorToken): array;
}
