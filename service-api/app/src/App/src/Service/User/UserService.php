<?php

namespace App\Service\User;

use App\DataAccess\Repository;
use App\Exception\ConflictException;
use ParagonIE\ConstantTime\Base64UrlSafe;

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
     * @throws ConflictException
     */
    public function add(array $data) : array
    {
        if ($this->usersRepository->exists($data['email'])) {
            throw new ConflictException('User already exists with email address ' . $data['email']);
        }

        //  An unactivated user account can only exist for 24 hours before it is deleted
        $activationToken = Base64UrlSafe::encode(random_bytes(32));
        $activationTtl = time() + (60 * 60 * 24);

        return $this->usersRepository->add($data['email'], $data['password'], $activationToken, $activationTtl);
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

    /**
     * Activate a user account
     *
     * @param string $activationToken
     * @return array
     */
    public function activate(string $activationToken) : array
    {
        $userData = $this->usersRepository->getByToken($activationToken);

//TODO - handle this....



        return $userData;
    }
}
