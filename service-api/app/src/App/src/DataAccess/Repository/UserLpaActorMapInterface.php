<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

/**
 * Represents access to mappings between a User Account, an LPA, and the associated Actor on the LPA.
 *
 * Interface UserLpaActorMapInterface
 *
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
     * Returns the LPA relation record for the given token.
     *
     * @param string $lpaActorToken
     *
     * @return ?array
     */
    public function get(string $lpaActorToken): ?array;

    /**
     * Returns LPA relation records for the given user_id.
     *
     * @param string $userId
     *
     * @return ?array
     */
    public function getByUserId(string $userId): ?array;

    /**
     * Deletes a LPA relation. Should only be called if a rollback is needed.
     *
     * @param string $lpaActorToken
     *
     * @return array The record that was deleted
     */
    public function delete(string $lpaActorToken): array;

    /**
     * Activates a LPA relation record, enabling it for use by the user
     *
     * @param string $lpaActorToken
     *
     * @return array The record that was activated
     */
    public function activateRecord(string $lpaActorToken): array;

    /**
     * Renews the LPA relation records activation period using the supplied interval.
     *
     * @see https://www.php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @param string $lpaActorToken
     * @param string $expiryInterval The interval of when this record should expire
     *
     * @return array The record that was renewed
     */
    public function renewActivationPeriod(string $lpaActorToken, string $expiryInterval): array;
}
