<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\Exception\NotFoundException;
use Exception;

interface ActorUsersInterface
{
    /**
     * Add an actor user
     *
     * @param string $email
     * @param string $password
     * @param string $activationToken
     * @param int $activationTtl
     * @return array
     * @throws Exception
     */
    public function add(string $email, string $password, string $activationToken, int $activationTtl) : array;

    /**
     * Get an actor user from the database
     *
     * @param string $email
     * @return array
     * @throws NotFoundException
     */
    public function get(string $email) : array;

    /**
     * Activate the user account in the database using the token value
     *
     * @param string $activationToken
     * @return array
     */
    public function activate(string $activationToken) : array;

    /**
     * Check for the existence of an actor user
     *
     * @param string $email
     * @return bool
     * @throws NotFoundException
     */
    public function exists(string $email) : bool;
}
