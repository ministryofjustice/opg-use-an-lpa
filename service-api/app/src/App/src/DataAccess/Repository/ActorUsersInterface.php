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
 *     LastLogin: string,
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
     * @param HiddenString $password
     * @param string $activationToken
     * @param int $activationTtl
     * @return void
     * @throws CreationException
     */
    public function add(
        string $id,
        string $email,
        HiddenString $password,
        string $activationToken,
        int $activationTtl,
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
     * Gets an actor user when queried for by a password reset token
     *
     * @param string $resetToken
     * @return string
     * @throws NotFoundException
     */
    public function getIdByPasswordResetToken(string $resetToken): string;

    /**
     * Gets an actor user when queried for by a email reset token
     *
     * @param string $resetToken
     * @return string
     * @throws NotFoundException
     */
    public function getIdByEmailResetToken(string $resetToken): string;

    /**
     * Queries the NewEmail field to see if a user has requested to change their email to that same email
     *
     * @param string $newEmail
     * @return array
     */
    public function getUserByNewEmail(string $newEmail): array;

    /**
     * Activate the user account in the database using the token value
     *
     * @param string $activationToken
     * @return array
     * @throws NotFoundException
     */
    public function activate(string $activationToken): array;

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
     * Reset a password in the system using a reset token and the intended password
     *
     * @param string $id The Id of the user to reset the password for
     * @param HiddenString $password
     * @return bool The password reset was successful or not
     */
    public function resetPassword(string $id, HiddenString $password): bool;

    /**
     * Records a successful login against the actor user
     *
     * @param string $id
     * @param string $loginTime An ATOM format datetime string
     */
    public function recordSuccessfulLogin(string $id, string $loginTime): void;

    /**
     * Records a reset token against an actor user account
     *
     * @param string $email
     * @param string $resetToken
     * @param int $resetExpiry Seconds till token expires
     * @return array The worked on actor user record
     * @throws NotFoundException
     */
    public function recordPasswordResetRequest(string $email, string $resetToken, int $resetExpiry): array;

    /**
     * Records a email reset token and new email for a user account
     *
     * @param string $id
     * @param string $newEmail
     * @param string $resetToken
     * @param int $resetExpiry
     * @return array The actor user record for the request
     */
    public function recordChangeEmailRequest(string $id, string $newEmail, string $resetToken, int $resetExpiry): array;

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

    /**
     * resets activation with password and ttl
     *
     * @param string $id
     * @param HiddenString $password
     * @param int $activationTtl
     * @return mixed
     */
    public function resetActivationDetails(string $id, HiddenString $password, int $activationTtl): array;

    /**
     * updates the password field of a user with the supplied password. This is intended
     * to be used as a part of the password rehashing that occurs to keep us up to date with
     * standards
     *
     * @param string       $id
     * @param HiddenString $password
     * @return bool
     */
    public function rehashPassword(string $id, HiddenString $password): bool;
}
