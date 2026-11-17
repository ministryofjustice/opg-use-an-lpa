<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\NotFoundException;

/**
 * Interface for Data relating to Users of the Actor System.
 *
 * @psalm-type ActorUser = array{
 *     Id: string,
 *     Identity?: string,
 *     Email: string,
 *     LastLogin?: string,
 * }
 */
interface ActorUsersInterface
{
    /**
     * Add an actor user, pinning the identity to the new account.
     *
     * @param bool $ignoreOrphanIdentity Allows an existing 'IDENTITY#' record to be taken over, but only if the
     *                                   account it points to does not exist. Only intended to be used once an identity
     *                                   lookup has found no account.
     * @throws CreationException
     * @throws ConflictException
     */
    public function add(
        string $id,
        string $email,
        string $identity,
        string $now,
        bool $ignoreOrphanIdentity = false,
    ): void;

    /**
     * Get an actor user from the database
     *
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function get(string $id): array;

    /**
     * Get an actor user from the database using their email
     *
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function getByEmail(string $email): array;

    /**
     * Gets the account that holds an identity. The identity pin is consulted first (consistently) so a
     * recently created account is always found. Where duplicate accounts hold an identity, and the pin does not
     * settle which owns it, no account is chosen.
     *
     * @psalm-return ActorUser
     * @throws NotFoundException
     * @throws ConflictException More than one account holds the identity
     */
    public function getByIdentity(string $identity): array;

    /**
     * Migrates a user account to being authenticated by OAuth
     *
     * @param bool $ignoreOrphanIdentity Take over an existing pin, but only if the account it points to does not
     *                                   exist. Verified atomically with the write.
     * @psalm-param ActorUser $user
     * @psalm-return ActorUser
     * @throws ConflictException
     * @throws CreationException
     */
    public function migrateToOAuth(
        array $user,
        string $identity,
        bool $ignoreOrphanIdentity = false,
    ): array;

    /**
     * Records a successful login against the actor user
     */
    public function recordSuccessfulLogin(string $id, string $loginTime): void;

    /**
     * Changes the email address for an account to the supplied new email
     */
    public function changeEmail(string $id, string $oldEmail, string $newEmail): void;

    /**
     * Deletes a user's account by account id
     *
     * @throws NotFoundException
     * @psalm-return ActorUser The deleted user details
     */
    public function delete(string $accountId): array;
}
