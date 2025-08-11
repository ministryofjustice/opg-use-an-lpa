<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\Exception\CreationException;
use App\Exception\NotFoundException;
use ParagonIE\HiddenString\HiddenString;

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
     * @param string $id
     * @param string $email
     * @param string $identity
     * @return void
     * @throws CreationException
     */
    public function add(
        string $id,
        string $email,
        string $identity,
    ): void;

    /**
     * Get an actor user from the database
     *
     * @param string $id
     * @return array
     * @throws NotFoundException
     */
    public function get(string $id): array;

    /**
     * Get an actor user from the database using their email
     *
     * @param string $email
     * @return array
     * @throws NotFoundException
     */
    public function getByEmail(string $email): array;

    /**
     * @param string $identity
     * @return array
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function getByIdentity(string $identity): array;

    /**
     * Migrates a user account to being authenticated by OAuth
     *
     * @param string $id
     * @param string $identity
     * @return array
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function migrateToOAuth(string $id, string $identity): array;

    /**
     * Check for the existence of an actor user
     *
     * @param string $email
     * @return bool
     */
    public function exists(string $email): bool;

    /**
     * Records a successful login against the actor user
     *
     * @param string $id
     * @param string $loginTime An ATOM format datetime string
     */
    public function recordSuccessfulLogin(string $id, string $loginTime): void;

    /**
     * Changes the email address for an account to the NewEmail chosen by the user
     * Also removes the email reset token, expiry and NewEmail attribute
     *
     * @param string $id
     * @param string $token
     * @param string $newEmail
     * @return bool
     */
    public function changeEmail(string $id, string $token, string $newEmail): bool;

    /**
     * Deletes a user's account by account id
     *
     * @param string $accountId
     * @throws NotFoundException
     * @return array The deleted user details
     */
    public function delete(string $accountId): array;
}
