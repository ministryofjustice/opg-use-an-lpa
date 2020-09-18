<?php

namespace Common\Service\User;

use Common\Entity\User;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Common\Service\Log\Output\Email;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Authentication\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class UserService
 * @package Common\Service\ApiClient
 */
class UserService implements UserRepositoryInterface
{
    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var callable
     */
    private $userModelFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UserService constructor.
     * @param ApiClient $apiClient
     * @param callable $userModelFactory
     */
    public function __construct(ApiClient $apiClient, callable $userModelFactory, LoggerInterface $logger)
    {
        $this->apiClient = $apiClient;

        // Provide type safety for the composed user factory.
        $this->userModelFactory = function (
            string $identity,
            array $roles = [],
            array $details = []
        ) use ($userModelFactory): UserInterface {
            return $userModelFactory($identity, $roles, $details);
        };

        $this->logger = $logger;
    }

    /**
     * @param string $email
     * @param HiddenString $password
     * @return array
     */
    public function create(string $email, HiddenString $password): array
    {
        $data = $this->apiClient->httpPost('/v1/user', [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->logger->info('Account with Id {id} created using email hash {email}', [
            'event_code' => EventCodes::ACCOUNT_CREATED,
            'id'         => $data['Id'],
            'email'      => new Email($email)
        ]);

        return $data;
    }

    /**
     * @param string $email
     * @return array
     */
    public function getByEmail(string $email): array
    {
        return $this->apiClient->httpGet('/v1/user', [
            'email' => $email,
        ]);
    }

    /**
     * Attempts authentication of a user based on the passed in credentials.
     *
     * @param string $email
     * @param string $password
     * @return User|null
     */
    public function authenticate(string $email, string $password = null): ?UserInterface
    {
        try {
            $userData = $this->apiClient->httpPatch('/v1/auth', [
                'email' => strtolower(trim($email)),
                'password' => $password,
            ]);

            if (!is_null($userData)) {
                $this->logger->info(
                    'Authentication successful for account with Id {id}',
                    [
                        'id'         => $userData['Id'],
                        'last-login' => $userData['LastLogin']
                    ]
                );

                return ($this->userModelFactory)(
                    $userData['Id'],
                    [],
                    [
                        'Email'     => $userData['Email'],
                        'LastLogin' => $userData['LastLogin']
                    ]
                );
            }
        } catch (ApiException $e) {
            $this->logger->notice(
                'Authentication failed for {email} with code {code}',
                [
                    'code'  => $e->getCode(),
                    'email' => $email
                ]
            );
            if ($e->getCode() === StatusCodeInterface::STATUS_UNAUTHORIZED) {
                // inactive accounts have status not authorized
                // we need to pick this up on the login to redirect to the activation resend page.
                throw $e;
            }
        } catch (Exception $e) {
            throw new RuntimeException('Marshaling user login datetime to DateTime failed', 500, $e);
        }

        return null;
    }

    /**
     * @param string $activationToken
     * @return bool
     * @throws \Http\Client\Exception
     */
    public function activate(string $activationToken): bool
    {
        try {
            $userData = $this->apiClient->httpPatch('/v1/user-activation', [
                'activation_token' => $activationToken,
            ]);

            if (is_array($userData) && !empty($userData)) {
                $this->logger->notice(
                    'Account with Id {id} has been activated',
                    [
                        'event_code' => EventCodes::ACCOUNT_ACTIVATED,
                        'id'         => $userData['Id']
                    ]
                );

                return true;
            }
        } catch (ApiException $ex) {
            if ($ex->getCode() !== StatusCodeInterface::STATUS_NOT_FOUND) {
                throw $ex;
            }
        }

        $this->logger->notice(
            'Account activation token {token} is invalid',
            [
                'token' => $activationToken
            ]
        );

        return false;
    }

    public function requestPasswordReset(string $email): string
    {
        $data = $this->apiClient->httpPatch('/v1/request-password-reset', [
            'email' => $email,
        ]);

        if (!is_null($data) && isset($data['PasswordResetToken'])) {
            $this->logger->info(
                'Account with Id {id} has requested a password reset',
                [
                    'id' => $data['Id']
                ]
            );

            return $data['PasswordResetToken'];
        }

        throw new RuntimeException('Error whilst requesting password reset token', 500);
    }

    public function canPasswordReset(string $token): bool
    {
        try {
            $data = $this->apiClient->httpGet('/v1/can-password-reset', [
                'token' => $token,
            ]);

            if (!is_null($data) && isset($data['Id'])) {
                $this->logger->info(
                    'Password reset token for account with Id {id} was used successfully',
                    [
                        'id' => $data['Id']
                    ]
                );

                return true;
            }
        } catch (ApiException $ex) {
            if ($ex->getCode() !== StatusCodeInterface::STATUS_GONE) {
                throw $ex;
            }
        }

        $this->logger->notice(
            'Password reset token {token} is invalid',
            [
                'token' => $token
            ]
        );

        return false;
    }

    public function completePasswordReset(string $token, HiddenString $password): void
    {
        $this->apiClient->httpPatch('/v1/complete-password-reset', [
            'token' => $token,
            'password' => $password->getString(),
        ]);

        $this->logger->info(
            'Password reset using token {token} has been successful',
            [
                'token' => $token
            ]
        );
    }

    public function requestChangeEmail(string $userId, string $newEmail, HiddenString $password): array
    {
        try {
            $data = $this->apiClient->httpPatch('/v1/request-change-email', [
                'user-id'       => $userId,
                'new-email'     => $newEmail,
                'password'      => $password->getString()
            ]);

            if (!is_null($data) && isset($data['EmailResetToken'])) {
                $this->logger->info(
                    'Account with Id {id} has requested a email reset',
                    [
                        'id' => $data['Id']
                    ]
                );

                return $data;
            }
        } catch (ApiException $ex) {
            $this->logger->notice(
                'Failed to request email change for account with Id {id} with code {code}',
                [
                    'id'    => $userId,
                    'code'  => $ex->getCode()
                ]
            );

            throw $ex;
        }

        throw new RuntimeException('Error whilst requesting email reset token', 500);
    }

    public function canResetEmail(string $token): bool
    {
        try {
            $data = $this->apiClient->httpGet('/v1/can-reset-email', [
                'token' => $token,
            ]);

            if (!is_null($data) && isset($data['Id'])) {
                $this->logger->info(
                    'Email reset token for account with Id {id} was used successfully',
                    [
                        'id' => $data['Id']
                    ]
                );

                return true;
            }
        } catch (ApiException $ex) {
            if ($ex->getCode() !== StatusCodeInterface::STATUS_GONE) {
                throw $ex;
            }
        }

        $this->logger->notice(
            'Email reset token {token} is invalid',
            [
                'token' => $token
            ]
        );

        return false;
    }

    public function completeChangeEmail(string $resetToken): void
    {
        $this->apiClient->httpPatch('/v1/complete-change-email', [
            'reset_token' => $resetToken,
        ]);

        $this->logger->info(
            'Email reset using token {token} has been successful',
            [
                'token' => $resetToken
            ]
        );
    }

    public function changePassword(string $id, HiddenString $password, HiddenString $newPassword): void
    {
        try {
            $this->apiClient->httpPatch('/v1/change-password', [
                'user-id'       => $id,
                'password'      => $password->getString(),
                'new-password'  => $newPassword->getString()
            ]);

            $this->logger->info(
                'Password reset for user ID {userId} has been successful',
                ['userId' => $id]
            );
        } catch (ApiException $ex) {
            $this->logger->notice(
                'Failed to change password for user ID {userId} with code {code}',
                [
                    'userId'    => $id,
                    'code'      => $ex->getCode()
                ]
            );

            throw $ex;
        }
    }

    public function deleteAccount(string $accountId): void
    {
        try {
            $user = $this->apiClient->httpDelete('/v1/delete-account/' . $accountId);

            $this->logger->notice(
                'Successfully deleted account with id {id} and email hash {email}',
                [
                    'event_code' => EventCodes::ACCOUNT_DELETED,
                    'id'         => $accountId,
                    'email'      => new Email($user['Email']),
                ]
            );
        } catch (ApiException $ex) {
            $this->logger->notice(
                'Failed to delete account for userId {userId} - status code {code}',
                [
                    'userId' => $accountId,
                    'code'   => $ex->getCode()
                ]
            );

            throw $ex;
        }
    }
}
