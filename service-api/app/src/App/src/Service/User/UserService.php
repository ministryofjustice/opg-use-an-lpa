<?php

namespace App\Service\User;

use App\DataAccess\Repository;
use App\Exception\ConflictException;

/**
 * Class UserService
 * @package App\Service\User
 */
class UserService
{
    /**
     * @var Repository\ActorUsersInterface
     */
    private $usersRepository;

    /**
     * UserService constructor.
     * @param Repository\ActorUsersInterface $usersRepository
     */
    public function __construct(Repository\ActorUsersInterface $usersRepository)
    {
        $this->usersRepository = $usersRepository;
    }

    /**
     * @param array $data
     * @return array
     */
    public function add(array $data) : array
    {
        if ($this->usersRepository->exists($data['email'])) {
            throw new ConflictException('User already exists with email address ' . $data['email']);
        }

        return $this->usersRepository->add($data['email'], $data['password']);
    }

    /**
     * Get an actor user using the email address
     *
     * @param string $email
     * @return array
     * @throws \Exception
     */
    public function get(string $email) : array
    {
        return $this->usersRepository->get($email);
    }
}
