<?php

namespace App\Service\User;

use App\DataAccess\Repository;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;

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
        //  First try to get any existing user
        try {
            $userData = $this->usersRepository->get($data['email']);

            //  There is a user already a user with this email address so throw an exception
            throw new ConflictException('User already exists with email address ' . $data['email']);
        } catch (NotFoundException $nfe) {
            // Ignore
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
