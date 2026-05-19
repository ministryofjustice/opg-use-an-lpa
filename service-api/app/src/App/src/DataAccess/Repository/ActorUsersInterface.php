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
 *     Password?: string,
 *     LastLogin?: string,
 *     ActivationToken?: string,
 *     ExpiresTTL?: int,
 *     PasswordResetToken?: string,
 *     PasswordResetExpiry?: int,
 *     NeedsReset?: bool,
 *     EmailResetToken?: string,
 *     EmailResetExpiry?: int,
 *     NewEmail?: string,
 * }
 */
interface ActorUsersInterface
{
    /**
     * Add an actor user
     *
     * @param bool $ignoreOrphanIdentity Forces to the operation to succeed by ignoring an exisiting 'IDENTITY#' record.
     *                                   Only intended to be used in cases where the record is known to be an orphan.
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
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function getByIdentity(string $identity): array;

    /**
     * Migrates a user account to being authenticated by OAuth
     *
     * @psalm-param ActorUser $user
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function migrateToOAuth(array $user, string $identity): array;

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
