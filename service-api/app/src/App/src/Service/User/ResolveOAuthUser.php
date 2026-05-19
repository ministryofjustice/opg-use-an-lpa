<?php

declare(strict_types=1);

namespace App\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Service\Log\EventCodes;
use App\Exception\{ConflictException, CreationException, NotFoundException};
use App\Service\Log\Output\Email;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateTimeInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Exception;

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
        private RecoverAccount $recoverAccount,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $identity
     * @param string $email
     * @return array
     * @psalm-return ActorUser
     * @throws CreationException|ConflictException|NotFoundException
     */
    public function __invoke(string $identity, string $email): array
    {
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

        // Ensure we don't return our Password over the wire. Although we don't set this anymore
        // the majority of records still have an associated password.
        unset($user['Password']);

        return $user;
    }

    /**
     * @psalm-return ?ActorUser A user if found
     */
    private function attemptToFetchUserByIdentity(string $identity, string $email): ?array
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

            if (!isset($user['Email']) || $user['Email'] !== $email) {
                $user = $this->userUpdate($user, $email);
            }
        } catch (NotFoundException) {
            return null;
        }

        return $user;
    }

    /**
     * @psalm-return ?ActorUser A user if found
     */
    private function attemptToFetchUserByEmail(string $identity, string $email): ?array
    {
        try {
            $user = $this->userService->getByEmail($email);

            // UML-4300 don't migrate accounts with existing OneLogin Identities.
            // this is a change to behaviour and may need a revisit. As currently implemented
            // in this class this will result in a new account being created.
            if (isset($user['Identity'])) {
                $this->logger->info(
                    'User already has assigned identity {identity}, not migrating',
                    [
                        'identity'   => $user['Identity'],
                        'email'      => new Email($email),
                        'event_code' => EventCodes::AUTH_ONELOGIN_ACCOUNT_CONTAINS_IDENTITY,
                    ]
                );
                return null;
            }

            $user = $this->usersRepository->migrateToOAuth($user, $identity);

            $this->logger->info(
                'Migrated existing account with email {email} to OIDC login',
                [
                    'identity'   => $identity,
                    'email'      => new Email($email),
                    'event_code' => EventCodes::AUTH_ONELOGIN_ACCOUNT_MIGRATED,
                ]
            );
        } catch (NotFoundException) {
            return null;
        }

        return $user;
    }

    /**
     * @psalm-return ActorUser The user just created
     * @throws ConflictException
     * @throws CreationException
     */
    private function addNewUser(string $identity, string $email): array
    {
        try {
            $user = $this->userService->add($email, $identity);

            $this->logger->info(
                'Created new OIDC login for account with email {email}',
                [
                    'identity'   => $identity,
                    'email'      => new Email($email),
                    'event_code' => EventCodes::AUTH_ONELOGIN_ACCOUNT_CREATED,
                ]
            );
        } catch (ConflictException $e) {
            /**
             * if the exception contains an identity as a reason then it is an orphan identity and we can
             * work around it. this should only be used assuming that we've already identified that an account
             * doesn't already exist with this identity @see attemptToFetchUserByIdentity()
             */
            if (count($e->getAdditionalData()) === 1 && isset($e->getAdditionalData()['identity'])) {
                $user = $this->userService->addWithOrphanIdentityBypass($email, $identity);
            } else {
                throw $e;
            }

            $this->logger->info(
                'Created new OIDC login for account with email {email} with orphan bypass',
                [
                    'identity'   => $identity,
                    'email'      => new Email($email),
                    'event_code' => EventCodes::AUTH_ONELOGIN_ACCOUNT_CREATED_WITH_ORPHAN_BYPASS,
                ]
            );
        }

        return $user;
    }

    /**
     * @param array $user
     * @psalm-param ActorUser $user
     * @param string $email
     * @return array
     * @psalm-return ActorUser
     */
    private function userUpdate(array $user, string $email): array
    {
        try {
            return ($this->recoverAccount)($user, $email) ?? $this->updateEmail($user, $email);
        } catch (DynamoDbException $ex) {
            $reasons = $ex->toArray()['CancellationReasons'] ?? [];

            foreach ($reasons as $reason) {
                if ($reason['Code'] === 'ConditionalCheckFailed') {
                    throw new ConflictException('User already exists with identity ' . $user['Identity']);
                }
            }

            throw $ex;
        }
    }

    /**
     * @param array $user
     * @psalm-param ActorUser $user
     * @param string $email
     * @return array
     * @psalm-return ActorUser
     */
    private function updateEmail(array $user, string $email): array
    {
        // update the held email
        $this->usersRepository->changeEmail($user['Id'], $user['Email'], $email);

        $logEmail      = $email;
        $user['Email'] = $email;

        $this->logger->info(
            'Update of email address for OIDC identity {identity} required',
            [
                'identity'  => $user['Identity'],
                'old_email' => new Email($logEmail),
                'new_email' => new Email($email),
            ]
        );

        return $user;
    }
}
