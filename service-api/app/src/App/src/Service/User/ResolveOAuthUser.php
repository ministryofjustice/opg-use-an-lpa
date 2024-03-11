<?php

declare(strict_types=1);

namespace App\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\{ConflictException, CreationException, DateTimeException, NotFoundException, RandomException};
use App\Service\Log\Output\Email;
use DateTimeInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * Given an OIDC 'sub' identity and an email it attempts to resolve a user out of our database.
 *
 * Failing to find one will result in the creation of a new local account record
 *
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
class ResolveOAuthUser
{
    public function __construct(
        private ActorUsersInterface $usersRepository,
        private UserService $userService,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $identity
     * @param string $email
     * @return array
     * @throws CreationException|ConflictException|NotFoundException
     */
    public function __invoke(string $identity, string $email): array
    {
        // attempt to fetch user by given id.
        $user = $this->attemptToFetchUserByIdentity($identity, $email);

        if ($user === null) {
            // attempt to fetch user by email (account migration)
            $user = $this->attemptToFetchUserByEmail($identity, $email);
        }

        if ($user === null) {
            // create new user account
            $user = $this->addNewUser($identity, $email);
        }

        $this->usersRepository->recordSuccessfulLogin(
            $user['Id'],
            $this->clock->now()->format(DateTimeInterface::ATOM)
        );

        // Ensure we don't return our Password over the wire.
        unset($user['Password']);

        return $user;
    }

    /**
     * @param string $email
     * @param string $identity
     * @return ?array
     * @psalm-return ?ActorUser
     */
    public function attemptToFetchUserByIdentity(string $identity, string $email): ?array
    {
        try {
            $user = $this->userService->getByIdentity($identity);

            $this->logger->info(
                'Found account matching OIDC identity {identity}',
                [
                    'identity' => $identity,
                    'email'    => new Email($email),
                ]
            );

            if ($user['Email'] !== $email) {
                // update the held email
                $this->usersRepository->changeEmail($user['Id'], '', $email);

                $logEmail      = $email;
                $user['Email'] = $email;

                $this->logger->info(
                    'Update of email address for OIDC identity {identity} required',
                    [
                        'identity'  => $identity,
                        'old_email' => new Email($logEmail),
                        'new_email' => new Email($email),
                    ]
                );
            }
        } catch (NotFoundException) {
            return null;
        }

        return $user;
    }

    /**
     * @param string $identity
     * @param string $email
     * @return ?array
     * @psalm-return ?ActorUser
     */
    public function attemptToFetchUserByEmail(string $identity, string $email): ?array
    {
        try {
            $user = $this->usersRepository->migrateToOAuth($this->userService->getByEmail($email)['Id'], $identity);

            $this->logger->info(
                'Migrated existing account with email {email} to OIDC login',
                [
                    'identity' => $identity,
                    'email'    => new Email($email),
                ]
            );
        } catch (NotFoundException) {
            return null;
        }

        return $user;
    }

    /**
     * @param string $identity
     * @param string $email
     * @return array
     * @throws ConflictException|CreationException|NotFoundException
     */
    public function addNewUser(string $identity, string $email): array
    {
        try {
            $user = $this->userService->add(['email' => $email]);
            $user = $this->usersRepository->migrateToOAuth($user['Id'], $identity);

            $this->logger->info(
                'Created new OIDC login for account with email {email}',
                [
                    'identity' => $identity,
                    'email'    => new Email($email),
                ]
            );

            return $user;
        } catch (ConflictException $e) {
            $this->logger->notice(
                'Creation of new OAuth account failed due to existing account with matching NewEmail field',
                ['email' => new Email($email)]
            );

            throw $e;
        } catch (DateTimeException | RandomException $e) {
            throw new CreationException('Low level PHP error occurred whilst attempting to add user', [], $e);
        }
    }
}
