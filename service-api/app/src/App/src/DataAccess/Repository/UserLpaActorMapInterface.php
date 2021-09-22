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
     * @param string $userId    The UserID of the actors account
     * @param string $siriusUid The Sirius formatted UID that is associated with an LPA in the system
     * @param string $actorId   The Sirius formatted UID that is associated with an actor in the system
     * @param string|null $expiryInterval The interval of when this record should expire.
     *                                    If null the record will not expire
     *
     * @return string The lpaActorToken of the newly created mapping
     */
    public function create(
        string $userId,
        string $siriusUid,
        string $actorId,
        string $expiryInterval = null
    ): string;

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
    public function getByUserId(string $userId): ?array;

    /**
     * Deletes an relation. Should only be called if a rollback is needed.
     *
     * @param string $lpaActorToken
     * @return mixed
     */
    public function delete(string $lpaActorToken): array;

    public function removeActivateBy(string $lpaActorToken): array;
}
