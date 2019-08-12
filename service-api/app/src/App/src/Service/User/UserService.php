<?php

declare(strict_types=1);

namespace App\Service\User;

use App\DataAccess\Repository;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\UnauthorizedException;
use Exception;
use ParagonIE\ConstantTime\Base64UrlSafe;

use function password_verify;

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
     * @throws Exception|CreationException|ConflictException
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
     * @throws NotFoundException
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
        return $this->usersRepository->activate($activationToken);
    }

    /**
     * Attempts authentication of a user
     *
     * @param string $email
     * @param string $password
     * @return array
     * @throws NotFoundException|ForbiddenException|UnauthorizedException
     */
    public function authenticate(string $email, string $password) : array
    {
        // TODO Remove development code
//        if ($email === 'a@b.com' && $password ='test') {
//            return [
//                'Email' => 'a@b.com',
//                'Password' => '$2y$10$Ew4y5jzm6fGKAB16huUw6ugZbuhgW5cvBQ6DGVDFzuyBXsCw51dzq'
//            ];
//        }

        $user = $this->usersRepository->get($email);

        if ( ! password_verify($password, $user['Password'])) {
            throw new ForbiddenException('Authentication failed');
        }

        if (array_key_exists('ActivationToken', $user)) {
            throw new UnauthorizedException('User account not verified');
        }

        $this->usersRepository->recordSuccessfulLogin($email);

        return $user;
    }
}
