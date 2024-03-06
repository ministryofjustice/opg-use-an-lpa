<?php

declare(strict_types=1);

namespace App\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\BadRequestException;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\DateTimeException;
use App\Exception\ForbiddenException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Exception\RandomException;
use App\Exception\UnauthorizedException;
use App\Service\Log\Output\Email;
use App\Service\RandomByteGenerator;
use DateTime;
use DateTimeInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\HiddenString\HiddenString;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

use function password_verify;

/**
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
class UserService
{
    public function __construct(
        private ActorUsersInterface $usersRepository,
        private ClockInterface $clock,
        private RandomByteGenerator $byteGenerator,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array{
     *     email: string,
     *     password?: HiddenString,
     * } $data
     * @return array{
     *     Id: string,
     *     ActivationToken: string,
     *     ExpiresTTL: int,
     * }
     * @throws CreationException|ConflictException|RandomException|DateTimeException|NotFoundException
     */
    public function add(array $data): array
    {
        if ($this->usersRepository->exists($data['email'])) {
            $user = $this->getByEmail($data['email']);

            if (isset($user['ActivationToken']) && isset($user['ExpiresTTL'])) {
                //we're not activated yet, so push forward the time and update password (as this may change)
                return $this->usersRepository->resetActivationDetails(
                    $user['Id'],
                    $data['password'],
                    $this->getExpiryTtl()
                );
            } else {
                //already activated.
                throw new ConflictException(
                    'User already exists with email address ' . $data['email'],
                    ['email' => $data['email']]
                );
            }
        }

        $emailResetExists = $this->usersRepository->getUserByNewEmail($data['email']);

        if (!empty($emailResetExists) && !$this->checkIfEmailResetViable($emailResetExists, true)) {
            //checks if the new email chosen has already been requested for reset
            $this->logger->notice(
                'Could not create account with email {email} as another user has already requested to ' .
                    'change their email that email address',
                ['email' => $data['email']]
            );

            throw new ConflictException(
                'Account creation email conflict - another user has requested to change their ' .
                    'email to ' . $data['email'],
                ['email' => $data['email']]
            );
        }

        // Generate unique id for user
        $id = $this->generateUniqueId();

        // If a password is not supplied, make a random one.
        $password = $data['password'] ?? new HiddenString(($this->byteGenerator)(32));

        //  An unactivated user account can only exist for 24 hours before it is deleted
        $activationToken = $this->getLinkToken();
        $activationTtl   = $this->getExpiryTtl();

        $this->usersRepository->add($id, $data['email'], $password, $activationToken, $activationTtl);

        $this->logger->info(
            'Account with Id {id} created using email {email}',
            [
                'id'    => $id,
                'email' => new Email($data['email']),
            ]
        );

        return ['Id' => $id, 'ActivationToken' => $activationToken, 'ExpiresTTL' => $activationTtl];
    }

    /**
     * Get an actor user using the email address
     *
     * @param string $email
     * @return array
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function getByEmail(string $email): array
    {
        return $this->usersRepository->getByEmail($email);
    }

    /**
     * @param string $identity
     * @return array
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function getByIdentity(string $identity): array
    {
        return $this->usersRepository->getByIdentity($identity);
    }

    /**
     * Activate a user account
     *
     * @param string $activationToken
     * @return array
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function activate(string $activationToken): array
    {
        $user = $this->usersRepository->activate($activationToken);

        $this->logger->info(
            'Account with Id {id} has been activated',
            ['id' => $user['Id']]
        );

        return $user;
    }

    /**
     * Attempts authentication of a user
     *
     * @param string $email
     * @param HiddenString $password
     * @return array
     * @throws NotFoundException|ForbiddenException|UnauthorizedException
     */
    public function authenticate(string $email, HiddenString $password): array
    {
        $user = $this->usersRepository->getByEmail($email);

        if (!password_verify($password->getString(), $user['Password'])) {
            throw new ForbiddenException('Authentication failed for email ' . $email, ['email' => $email]);
        }

        // as the login is successful ensure that we're updating our password storage inline with
        // current best practices.
        if (password_needs_rehash($user['Password'], PASSWORD_DEFAULT, ['cost' => 13])) {
            $this->usersRepository->rehashPassword($user['Id'], $password);
        }

        if (array_key_exists('ActivationToken', $user)) {
            throw new UnauthorizedException(
                'Authentication attempted against inactive account with Id ' . $user['Id'],
                ['id' => $user['Id']]
            );
        }

        $this->usersRepository->recordSuccessfulLogin(
            $user['Id'],
            $this->clock->now()->format(DateTimeInterface::ATOM)
        );

        // Ensure we don't return our Password over the wire.
        unset($user['Password']);

        $this->logger->info(
            'Authentication successful for account with Id {id}',
            [
                'id'         => $user['Id'],
                'last-login' => $user['LastLogin'] ?? null,
            ]
        );

        return $user;
    }

    /**
     * Generates a password reset token and ensures it's stored
     * against the actor record alongside its expiry time.
     *
     * @param string $email
     * @return array
     * @throws NotFoundException|RandomException
     */
    public function requestPasswordReset(string $email): array
    {
        $resetToken  = $this->getLinkToken();
        $resetExpiry = $this->getExpiryTtl();

        try {
            $user = $this->usersRepository->recordPasswordResetRequest($email, $resetToken, $resetExpiry);
        } catch (NotFoundException $e) {
            $this->logger->notice(
                'Attempt made to reset password for non-existent account',
                ['email' => new Email($email)]
            );

            throw $e;
        }

        $this->logger->info(
            'Account with Id {id} has requested a password reset',
            ['id' => $user['Id']]
        );

        return $user;
    }

    /**
     * Checks to see if a token exists against a user record and it has not expired
     *
     * @param string $resetToken
     * @return string
     * @throws GoneException     Password reset token was not found
     * @throws DateTimeException Unable to construct datetime using retrieved data
     */
    public function canResetPassword(string $resetToken): string
    {
        try {
            // PasswordResetToken index is KEY only so fetch the id to do work on
            $userId = $this->usersRepository->getIdByPasswordResetToken($resetToken);

            $user = $this->usersRepository->get($userId);

            try {
                $date = new DateTime('@' . $user['PasswordResetExpiry']);
            } catch (Throwable $e) {
                throw new DateTimeException($e->getMessage(), (int) $e->getCode(), $e);
            }

            if ($date >= $this->clock->now()) {
                return $userId;
            }
        } catch (NotFoundException) {
            $this->logger->notice(
                'Account not found for password reset token {token}',
                ['token' => $resetToken]
            );
        }

        throw new GoneException('Password reset token not found');
    }

    /**
     * Accepts a previously generated token and new password and attempts to
     * reset the users password to the new value if the token is found and has
     * not expired.
     *
     * @param string       $resetToken
     * @param HiddenString $password
     * @throws BadRequestException Password reset token has expired
     * @throws NotFoundException   Account was not found
     * @throws DateTimeException   Unable to coerce a date from a supplied value
     */
    public function completePasswordReset(string $resetToken, HiddenString $password): void
    {
        // PasswordResetToken index is KEY only so fetch the id to do work on
        $userId = $this->usersRepository->getIdByPasswordResetToken($resetToken);

        $user = $this->usersRepository->get($userId);

        try {
            if (new DateTime('@' . $user['PasswordResetExpiry']) < $this->clock->now()) {
                throw new BadRequestException(
                    'Password reset token has expired for account with Id ' . $userId,
                    ['id' => $userId]
                );
            }
        } catch (BadRequestException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new DateTimeException($e->getMessage(), (int) $e->getCode(), $e);
        }

        // also removes reset token
        $this->usersRepository->resetPassword($userId, $password);

        $this->logger->info(
            'Password reset for account with Id {id} was successful',
            ['id' => $userId]
        );
    }

    /**
     * @param string       $userId
     * @param HiddenString $password
     * @param HiddenString $newPassword
     * @return void
     * @throws ForbiddenException Password verification failed
     * @throws NotFoundException  Account was not found
     */
    public function completeChangePassword(string $userId, HiddenString $password, HiddenString $newPassword): void
    {
        $user = $this->usersRepository->get($userId);

        if (!password_verify($password->getString(), $user['Password'])) {
            throw new ForbiddenException(
                'Authentication failed for user ID ' . $userId,
                ['userId' => $userId]
            );
        }

        $this->usersRepository->resetPassword($userId, $newPassword);
    }

    /**
     * @param string $accountId
     * @return array
     * @throws NotFoundException Account was not found
     */
    public function deleteUserAccount(string $accountId): array
    {
        $user = $this->usersRepository->get($accountId);

        return $this->usersRepository->delete($user['Id']);
    }

    /**
     * @param string       $userId
     * @param string       $newEmail
     * @param HiddenString $password
     * @return array
     * @throws ConflictException  The email is already used by an account or has already been requested
     * @throws DateTimeException  Unable to coerce a date from a supplied value
     * @throws ForbiddenException Password verification failed
     * @throws NotFoundException  Account was not found
     * @throws RandomException    Generation of random token failed
     */
    public function requestChangeEmail(string $userId, string $newEmail, HiddenString $password): array
    {
        $resetToken  = $this->getLinkToken();
        $resetExpiry = $this->getExpiryTtl();

        $this->canRequestChangeEmail($userId, $newEmail, $password);

        $data = $this->usersRepository->recordChangeEmailRequest($userId, $newEmail, $resetToken, $resetExpiry);

        $this->logger->info(
            'Change email request for account with Id {id} was successful',
            ['id' => $userId]
        );

        return $data;
    }

    /**
     * Runs a series of checks on the new email and password
     *
     * @param string       $userId
     * @param string       $newEmail
     * @param HiddenString $password
     * @return void
     * @throws ConflictException  The email is already used by an account or has already been requested
     * @throws DateTimeException  Unable to coerce a date from a supplied value
     * @throws ForbiddenException Password verification failed
     * @throws NotFoundException  Account was not found
     */
    public function canRequestChangeEmail(string $userId, string $newEmail, HiddenString $password): void
    {
        $user = $this->usersRepository->get($userId);

        if (!password_verify($password->getString(), $user['Password'])) {
            throw new ForbiddenException(
                'Authentication failed for user ID ' . $userId,
                ['userId' => $userId]
            );
        }

        if ($this->usersRepository->exists($newEmail)) {
            throw new ConflictException(
                'User already exists with email address ' . $newEmail,
                ['email' => $newEmail]
            );
        }

        $newEmailExists = $this->usersRepository->getUserByNewEmail($newEmail);

        if (
            !empty($newEmailExists) &&
            !$this->checkIfEmailResetViable($newEmailExists, false, $userId)
        ) {
            $this->logger->notice(
                'Could not request email change for account with Id {id}
                as another user has already requested to change their email that email address',
                ['id' => $userId]
            );

            throw new ConflictException(
                'Change email conflict - another user has already requested to change their email
                address to ' . $newEmail,
                ['email' => $newEmail]
            );
        }
    }

    /**
     * @param array       $emailResetExists
     * @param bool        $forAccountCreation
     * @param string|null $userId
     * @return bool
     * @throws DateTimeException
     */
    private function checkIfEmailResetViable(
        array $emailResetExists,
        bool $forAccountCreation,
        ?string $userId = null,
    ): bool {
        try {
            //checks if the new email chosen has already been requested for reset
            foreach ($emailResetExists as $otherUser) {
                if (
                    new DateTime('@' . $otherUser['EmailResetExpiry']) >= $this->clock->now() &&
                    ($forAccountCreation || $userId !== $otherUser['Id'])
                ) {
                    // if the other users email reset token has not expired, this user cannot create an account
                    // with this email
                    return false;
                }
            }
            return true;
        } catch (Throwable $e) {
            throw new DateTimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Checks to see if an email token exists against a user record and it has not expired
     *
     * @param string $resetToken
     * @return string
     * @throws DateTimeException|GoneException
     */
    public function canResetEmail(string $resetToken): string
    {
        try {
            $userId = $this->usersRepository->getIdByEmailResetToken($resetToken);

            $user = $this->usersRepository->get($userId);

            if (new DateTime('@' . $user['EmailResetExpiry']) >= $this->clock->now()) {
                return $userId;
            }
        } catch (NotFoundException) {
            $this->logger->notice(
                'Account not found for reset email token {token}',
                ['token' => $resetToken]
            );
        } catch (Throwable $e) {
            throw new DateTimeException($e->getMessage(), (int) $e->getCode(), $e);
        }

        throw new GoneException('Email reset token has expired');
    }

    /**
     * @throws NotFoundException
     */
    public function completeChangeEmail(string $resetToken): void
    {
        $userId = $this->usersRepository->getIdByEmailResetToken($resetToken);

        $user = $this->usersRepository->get($userId);

        $this->usersRepository->changeEmail($userId, $resetToken, $user['NewEmail']);
    }

    /**
     * Get link token
     *
     * @return string
     * @throws RandomException
     */
    private function getLinkToken(): string
    {
        return Base64UrlSafe::encode(($this->byteGenerator)(32));
    }

    /**
     * get Expiry TTL
     *
     * @return float|int
     */
    private function getExpiryTtl(): float|int
    {
        return time() + (60 * 60 * 24);
    }

    /**
     * Generate unique id (UUID)
     *
     * @return string
     */
    private function generateUniqueId(): string
    {
        return Uuid::uuid4()->toString();
    }
}
