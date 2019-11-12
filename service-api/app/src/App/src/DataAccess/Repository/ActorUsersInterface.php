<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\Exception\CreationException;
use App\Exception\NotFoundException;
use Exception;

/**
 * Interface for Data relating to Users of the Actor System.
 *
 * Interface ActorUsersInterface
 * @package App\DataAccess\Repository
 */
interface ActorUsersInterface
{
    /**
     * Add an actor user
     *
     * @param string $id
     * @param string $email
     * @param string $password
     * @param string $activationToken
     * @param int $activationTtl
     * @return array
     */
    public function add(string $id, string $email, string $password, string $activationToken, int $activationTtl) : array;

    /**
     * Get an actor user from the database
     *
     * @param string $id
     * @return array
     * @throws NotFoundException
     */
    public function get(string $id) : array;

    /**
     * Get an actor user from the database using their email
     *
     * @param string $email
     * @return array
     * @throws NotFoundException
     */
    public function getByEmail(string $email) : array;

    /**
     * Gets an actor user when queried for by a password reset token
     *
     * @param string $resetToken
     * @return string
     */
    public function getIdByPasswordResetToken(string $resetToken) : string;

    /**
     * Activate the user account in the database using the token value
     *
     * @param string $activationToken
     * @return array
     * @throws NotFoundException
     */
    public function activate(string $activationToken) : array;

    /**
     * Check for the existence of an actor user
     *
     * @param string $email
     * @return bool
     */
    public function exists(string $email) : bool;

    /**
     * Reset a password in the system using a reset token and the intended password
     *
     * @param string $id The Id of the user to reset the password for
     * @param string $password
     * @return bool The password reset was successful or not
     */
    public function resetPassword(string $id, string $password): bool;

    /**
     * Records a successful login against the actor user
     *
     * @param string $id
     * @param string $loginTime An ATOM format datetime string
     */
    public function recordSuccessfulLogin(string $id, string $loginTime) : void;

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
}
