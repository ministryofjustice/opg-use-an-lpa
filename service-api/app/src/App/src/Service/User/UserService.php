<?php

declare(strict_types=1);

namespace App\Service\User;

use App\DataAccess\Repository;
use App\Exception\BadRequestException;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\ForbiddenException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Exception\UnauthorizedException;
use App\Service\ApiClient\ApiException;
use DateTime;
use DateTimeInterface;
use Exception;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Ramsey\Uuid\Uuid;

use function password_verify;
use function random_bytes;

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
    public function add(array $data): array
    {
        if ($this->usersRepository->exists($data['email'])) {
            throw new ConflictException('User already exists with email address ' . $data['email']);
        }

        // Generate unique id for user
        $id = Uuid::uuid4()->toString();

        //  An unactivated user account can only exist for 24 hours before it is deleted
        $activationToken = Base64UrlSafe::encode(random_bytes(32));
        $activationTtl = time() + (60 * 60 * 24);

        return $this->usersRepository->add($id, $data['email'], $data['password'], $activationToken, $activationTtl);
    }

    /**
     * Get an actor user using the email address
     *
     * @param string $email
     * @return array
     * @throws NotFoundException
     */
    public function getByEmail(string $email): array
    {
        return $this->usersRepository->getByEmail($email);
    }

    /**
     * Activate a user account
     *
     * @param string $activationToken
     * @return array
     */
    public function activate(string $activationToken): array
    {
        return $this->usersRepository->activate($activationToken);
    }

    /**
     * Attempts authentication of a user
     *
     * @param string $email
     * @param string $password
     * @return array
     * @throws NotFoundException|ForbiddenException|UnauthorizedException|Exception
     */
    public function authenticate(string $email, string $password): array
    {
        $user = $this->usersRepository->getByEmail($email);

        if (! password_verify($password, $user['Password'])) {
            throw new ForbiddenException('Authentication failed');
        }

        if (array_key_exists('ActivationToken', $user)) {
            throw new UnauthorizedException('User account not verified');
        }

        $this->usersRepository->recordSuccessfulLogin(
            $user['Id'],
            (new DateTime('now'))->format(DateTimeInterface::ATOM)
        );

        return $user;
    }

    /**
     * Generates a password reset token and ensures it's stored
     * against the actor record alongside its expiry time.
     *
     * @param string $email
     * @return array
     * @throws Exception
     */
    public function requestPasswordReset(string $email): array
    {
        $resetToken = Base64UrlSafe::encode(random_bytes(32));
        $resetExpiry = time() + (60 * 60 * 24);

        return $this->usersRepository->recordPasswordResetRequest($email, $resetToken, $resetExpiry);
    }

    /**
     * Checks to see if a token exists against a user record and it has not expired
     *
     * @param string $resetToken
     * @return string
     * @throws Exception
     */
    public function canResetPassword(string $resetToken): string
    {
        try {
            // PasswordResetToken index is KEY only so fetch the id to do work on
            $userId = $this->usersRepository->getIdByPasswordResetToken($resetToken);

            $user = $this->usersRepository->get($userId);

            if (new DateTime('@' . $user['PasswordResetExpiry']) >= new DateTime('now')) {
                return $userId;
            }
        } catch (NotFoundException $ex) {
            // token not found in usersRepository
        }

        throw new GoneException('Reset token not found');
    }

    /**
     * Accepts a previously generated token and new password and attempts to
     * reset the users password to the new value if the token is found and has
     * not expired.
     *
     * @param string $resetToken
     * @param string $password
     * @throws Exception
     */
    public function completePasswordReset(string $resetToken, string $password): void
    {
        // PasswordResetToken index is KEY only so fetch the id to do work on
        $userId = $this->usersRepository->getIdByPasswordResetToken($resetToken);

        $user = $this->usersRepository->get($userId);

        if (new DateTime('@' . $user['PasswordResetExpiry']) < new DateTime('now')) {
            throw new BadRequestException('Password reset token has expired');
        }

        // also removes reset token
        $this->usersRepository->resetPassword($userId, $password);
    }
}
