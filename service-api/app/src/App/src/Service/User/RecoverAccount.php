<?php

declare(strict_types=1);

namespace App\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\NotFoundException;
use App\Service\Log\EventCodes;
use App\Service\Log\Output\Email;
use Psr\Log\LoggerInterface;

/**
 * Implements a rudimentary account recovery process that allows a changed OIDC email to trigger the reassignment
 * of the OIDC identity from an existing (new and empty) record to an older one that matches that email. Recovering
 * any LPAs that were a part of that older record.
 *
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
class RecoverAccount
{
    public function __construct(
        private ActorUsersInterface $usersRepository,
        private UserLpaActorMapInterface $userLpaActorMap,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array           $user
     * @psalm-param ActorUser $user
     * @param string          $email
     * @return ?array
     * @psalm-return ?ActorUser
     */
    public function __invoke(array $user, string $email): ?array
    {
        try {
            $existingAccount = $this->usersRepository->getByEmail($email);

            // should never happen but belt and braces.
            if ($user['Id'] === $existingAccount['Id']) {
                return null;
            }

            $hasLpas = count($this->userLpaActorMap->getByUserId($user['Id'])) > 0;

            // passed in user account does not have lpas and existing account is not already linked to OIDC
            if (! (array_key_exists('Identity', $existingAccount) || $hasLpas)) {
                $this->usersRepository->delete($user['Id']);

                $user = $this->usersRepository->migrateToOAuth($existingAccount, $user['Identity']);

                $this->logger->info(
                    'Recovered existing account with email {email} to OIDC login',
                    [
                        'identity'   => $user['Identity'],
                        'email'      => new Email($email),
                        'event_code' => EventCodes::AUTH_ONELOGIN_ACCOUNT_RECOVERED,
                    ]
                );

                return $user;
            }

            return null;
        } catch (NotFoundException) {
            // existing account for email not found
            return null;
        }
    }
}
