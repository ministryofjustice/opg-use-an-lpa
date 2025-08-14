<?php

declare(strict_types=1);

namespace App\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use App\Service\Log\Output\Email;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
class UserService
{
    public function __construct(
        private ActorUsersInterface $usersRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @psalm-return ActorUser The created user
     * @throws ConflictException
     * @throws CreationException
     */
    public function add(string $email, string $identity): array
    {
        if ($this->usersRepository->exists($email)) {
            throw new ConflictException(
                'User already exists with email address ' . $email,
                ['email' => $email]
            );
        }

        // Generate unique id for user
        $id = $this->generateUniqueId();

        $this->usersRepository->add($id, $email, $identity);

        $this->logger->info(
            'Account with Id {id} created for identity {identity} using email {email}',
            [
                'id'       => $id,
                'email'    => new Email($email),
                'identity' => $identity,
            ]
        );

        return ['Id' => $id, 'Email' => $email, 'Identity' => $identity];
    }

    /**
     * Get an actor user using the email address
     *
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function getByEmail(string $email): array
    {
        return $this->usersRepository->getByEmail($email);
    }

    /**
     * @psalm-return ActorUser
     * @throws NotFoundException
     */
    public function getByIdentity(string $identity): array
    {
        return $this->usersRepository->getByIdentity($identity);
    }

    /**
     * @psalm-return ActorUser The deleted user details
     * @throws NotFoundException Account was not found
     */
    public function deleteUserAccount(string $accountId): array
    {
        $user = $this->usersRepository->get($accountId);

        return $this->usersRepository->delete($user['Id']);
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
