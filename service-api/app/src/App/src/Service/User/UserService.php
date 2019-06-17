<?php

namespace App\Service\User;

use App\DataAccess\Repository;

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
     */
    public function add(array $data)
    {
        $this->usersRepository->add($data['email'], $data['password']);
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
