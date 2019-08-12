<?php

namespace Common\Service\User;

use Common\Entity\User;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Fig\Http\Message\StatusCodeInterface;
use ArrayObject;
use DateTime;
use Exception;
use RuntimeException;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Authentication\UserRepositoryInterface;

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
     * UserService constructor.
     * @param ApiClient $apiClient
     * @param callable $userModelFactory
     */
    public function __construct(ApiClient $apiClient, callable $userModelFactory)
    {
        $this->apiClient = $apiClient;

        // Provide type safety for the composed user factory.
        $this->userModelFactory = function (
            string $identity,
            array $roles = [],
            array $details = []
        ) use ($userModelFactory) : UserInterface {
            return $userModelFactory($identity, $roles, $details);
        };
    }

    /**
     * @param string $email
     * @param string $password
     * @return array
     */
    public function create(string $email, string $password) : array
    {
        return $this->apiClient->httpPost('/v1/user', [
            'email'    => $email,
            'password' => $password,
        ]);
    }

    /**
     * @param string $email
     * @return ArrayObject|null
     */
    public function getByEmail(string $email) : ?array
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
    public function authenticate(string $email, string $password = null) : ?UserInterface
    {
        try {
            $userData = $this->apiClient->httpPatch('/v1/auth', [
                'email' => $email,
                'password' => $password,
            ]);

            if ( ! is_null($userData)) {
                return ($this->userModelFactory)(
                    $userData['Email'],
                    [],
                    [
                        'LastLogin' => new DateTime($userData['LastLogin'])
                    ]
                );
            }
        } catch (ApiException $e) {
            // TODO log or otherwise report authentication issue?
        } catch (Exception $e) {
            throw new RuntimeException("Marshaling user login datetime to DateTime failed", 500, $e);
        }

        return null;
    }

    /**
     * @param string $activationToken
     * @return bool
     * @throws \Http\Client\Exception
     */
    public function activate(string $activationToken) : bool
    {
        try {
            $userData = $this->apiClient->httpPatch('/v1/user-activation', [
                'activation_token' => $activationToken,
            ]);

            if (is_array($userData) && !empty($userData)) {
                return true;
            }
        } catch (ApiException $ex) {
            if ($ex->getCode() != StatusCodeInterface::STATUS_NOT_FOUND) {
                throw $ex;
            }
        }

        return false;
    }
}
