<?php

namespace Common\Service\User;

use Common\Entity\User;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Fig\Http\Message\StatusCodeInterface;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Mezzio\Authentication\UserInterface;
use Mezzio\Authentication\UserRepositoryInterface;

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
     * @param string $password
     * @return array
     */
    public function create(string $email, string $password): array
    {
        $data = $this->apiClient->httpPost('/v1/user', [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->logger->info(
            'Account with Id {id} created using email {email}',
            [
                'id'    => $data['Id'],
                'email' => $email
            ]
        );

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
                'email' => $email,
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
                $this->logger->info(
                    'Account with Id {id} has been activated',
                    [
                        'id' => $userData['Id']
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

    public function completePasswordReset(string $token, string $password): void
    {
        $this->apiClient->httpPatch('/v1/complete-password-reset', [
            'token' => $token,
            'password' => $password
        ]);

        $this->logger->info(
            'Password reset using token {token} has been successful',
            [
                'token' => $token
            ]
        );
    }
}
