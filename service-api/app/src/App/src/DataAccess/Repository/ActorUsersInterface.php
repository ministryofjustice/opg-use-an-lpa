<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

interface ActorUsersInterface
{
    /**
     * Add an actor user
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    public function add(string $email, string $password) : array;

    /**
     * Get an actor user from the database
     *
     * @param string $email
     * @return array
     */
    public function get(string $email) : array;
}
