<?php

declare(strict_types=1);

namespace App\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\DateTimeException;
use App\Exception\NotFoundException;
use App\Exception\RandomException;
use App\Service\Log\Output\Email;
use App\Service\RandomByteGenerator;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
class UserService
{
    public function __construct(
        private ActorUsersInterface $usersRepository,
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
            throw new ConflictException(
                'User already exists with email address ' . $data['email'],
                ['email' => $data['email']]
            );
        }

        // Generate unique id for user
        $id = $this->generateUniqueId();

        // If a password is not supplied, make a random one.
        $password = $data['password'] ?? new HiddenString(bin2hex(($this->byteGenerator)(32)));

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
     * @return int
     */
    private function getExpiryTtl(): int
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
