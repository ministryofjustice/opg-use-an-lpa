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
use DateTime;
use DateTimeInterface;
use Exception;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Repository\ActorUsersInterface
     */
    private $usersRepository;

    /**
     * UserService constructor.
     *
     * @param Repository\ActorUsersInterface $usersRepository
     */
    public function __construct(Repository\ActorUsersInterface $usersRepository, LoggerInterface $logger)
    {
        $this->usersRepository = $usersRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception|CreationException|ConflictException
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

        if (!empty($emailResetExists) && ! $this->checkIfEmailResetViable($emailResetExists, true)) {
            //checks if the new email chosen has already been requested for reset
            $this->logger->notice(
                'Could not create account with email {email} as another user has already requested to change their email that email address',
                ['email' => $data['email']]
            );

            throw new ConflictException(
                'Account creation email conflict - another user has requested to change their email to ' . $data['email'],
                ['email' => $data['email']]
            );
        }

        // Generate unique id for user
        $id = $this->generateUniqueId();

        //  An unactivated user account can only exist for 24 hours before it is deleted
        $activationToken = $this->getLinkToken();
        $activationTtl = $this->getExpiryTtl();

        $user = $this->usersRepository->add($id, $data['email'], $data['password'], $activationToken, $activationTtl);

        $this->logger->info(
            'Account with Id {id} created using email {email}',
            [
                'id' => $id,
                'email' => $data['email'],
            ]
        );

        return $user;
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
     * @param string $password
     * @return array
     * @throws NotFoundException|ForbiddenException|UnauthorizedException|Exception
     */
    public function authenticate(string $email, string $password): array
    {
        $user = $this->usersRepository->getByEmail($email);

        if (! password_verify($password, $user['Password'])) {
            throw new ForbiddenException('Authentication failed for email ' . $email, ['email' => $email ]);
        }

        if (array_key_exists('ActivationToken', $user)) {
            throw new UnauthorizedException(
                'Authentication attempted against inactive account with Id ' . $user['Id'],
                ['id' => $user['Id']]
            );
        }

        $this->usersRepository->recordSuccessfulLogin(
            $user['Id'],
            (new DateTime('now'))->format(DateTimeInterface::ATOM)
        );

        $this->logger->info(
            'Authentication successful for account with Id {id}',
            [
                'id' => $user['Id'],
                'last-login' => $user['LastLogin'],
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
     * @throws Exception
     */
    public function requestPasswordReset(string $email): array
    {
        $resetToken = $this->getLinkToken();
        $resetExpiry = $this->getExpiryTtl();

        try {
            $user = $this->usersRepository->recordPasswordResetRequest($email, $resetToken, $resetExpiry);
        } catch (Exception $e) {
            $this->logger->notice(
                'Attempt made to reset password for non-existent account',
                ['email' => $email]
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
            throw new BadRequestException(
                'Password reset token has expired for account with Id ' . $userId,
                ['id' => $userId]
            );
        }

        // also removes reset token
        $this->usersRepository->resetPassword($userId, $password);

        $this->logger->info(
            'Password reset for account with Id {id} was successful',
            ['id' => $userId]
        );
    }

    /**
     * @param string $userId
     * @param string $password
     * @param string $newPassword
     */
    public function completeChangePassword(string $userId, string $password, string $newPassword): void
    {
        $user = $this->usersRepository->get($userId);

        if (! password_verify($password, $user['Password'])) {
            throw new ForbiddenException('Authentication failed for user ID ' . $userId, ['userId' => $userId]);
        }

        $this->usersRepository->resetPassword($userId, $newPassword);
    }

    /**
     * @param string $accountId
     * @return array
     */
    public function deleteUserAccount(string $accountId): array
    {
        $user = $this->usersRepository->get($accountId);

        if (is_null($user)) {
            $this->logger->notice(
                'Account not found for user Id {Id}',
                ['Id' => $accountId]
            );
            throw new NotFoundException('User not found for account with Id ' . $accountId);
        }

        return $this->usersRepository->delete($accountId);
    }

    /**
     * @param string $userId
     * @param string $newEmail
     * @param string $password
     * @return array
     * @throws Exception
     */
    public function requestChangeEmail(string $userId, string $newEmail, string $password)
    {
        $resetToken = $this->getLinkToken();
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
     * @param string $userId
     * @param string $newEmail
     * @param string $password
     * @return void
     * @throws Exception
     */
    public function canRequestChangeEmail(string $userId, string $newEmail, string $password): void
    {
        $user = $this->usersRepository->get($userId);

        if (! password_verify($password, $user['Password'])) {
            throw new ForbiddenException('Authentication failed for user ID ' . $userId, ['userId' => $userId]);
        }

        if ($this->usersRepository->exists($newEmail)) {
            throw new ConflictException('User already exists with email address ' . $newEmail, ['email' => $newEmail]);
        }

        $newEmailExists = $this->usersRepository->getUserByNewEmail($newEmail);

        if (!empty($newEmailExists) && ! $this->checkIfEmailResetViable($newEmailExists, false, $userId)) {
            $this->logger->notice(
                'Could not request email change for account with Id {id}
                as another user has already requested to change their email that email address',
                ['id' => $userId]
            );

            throw new ConflictException(
                'Change email conflict - another user has already requested to change their email address to ' . $newEmail,
                ['email' => $newEmail]
            );
        }
    }

    /**
     * @param array $emailResetExists
     * @param string $userId
     * @return bool
     * @throws Exception
     */
    private function checkIfEmailResetViable(array $emailResetExists, bool $forAccountCreation, string $userId = null): bool
    {
        //checks if the new email chosen has already been requested for reset
        foreach ($emailResetExists as $otherUser) {
            if ($forAccountCreation && (new DateTime('@' . $otherUser['EmailResetExpiry']) >= new DateTime('now'))) {
                // if the other users email reset token has not expired, this user cannot create an account with this email
                return false;
            } elseif (new DateTime('@' . $otherUser['EmailResetExpiry']) >= new DateTime('now') && ($userId !== $otherUser['Id'])) {
                // if the other users email reset token has not expired, and they not the current user, this user cant request this email
                return false;
            }
        }
        return true;
    }

    /**
     * Checks to see if an email token exists against a user record and it has not expired
     *
     * @param string $resetToken
     * @return string
     * @throws Exception
     */
    public function canResetEmail(string $resetToken)
    {
        try {
            $userId = $this->usersRepository->getIdByEmailResetToken($resetToken);

            $user = $this->usersRepository->get($userId);

            if (new DateTime('@' . $user['EmailResetExpiry']) >= new DateTime('now')) {
                return $userId;
            }
        } catch (NotFoundException $ex) {
            $this->logger->notice(
                'Account not found for reset email token {token}',
                ['token' => $resetToken]
            );
        }

        throw new GoneException('Email reset token has expired');
    }

    /**
     * @param string $resetToken
     */
    public function completeChangeEmail(string $resetToken)
    {
        $userId = $this->usersRepository->getIdByEmailResetToken($resetToken);

        $user = $this->usersRepository->get($userId);

        $this->usersRepository->changeEmail($userId, $resetToken, $user['NewEmail']);
    }

    /**
     * Get link token
     * @return string
     * @throws Exception
     */
    private function getLinkToken(): string
    {
        return Base64UrlSafe::encode(random_bytes(32));
    }

    /**
     * get Expiry TTL
     * @return float|int
     */
    private function getExpiryTtl()
    {
        return time() + (60 * 60 * 24);
    }

    /**
     * Generate unique id (UUID)
     * @return string
     * @throws Exception
     */
    private function generateUniqueId(): string
    {
        return Uuid::uuid4()->toString();
    }
}
