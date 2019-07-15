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

/**
 * Class UserService
 * @package Common\Service\ApiClient
 */
class UserService
{
    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * UserService constructor.
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
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
    public function authenticate(string $email, string $password) : ?User
    {
        try {
            $userData = $this->apiClient->httpGet('/v1/auth', [
                'email' => $email,
                'password' => $password,
            ]);

            return new User(
                $userData['id'],
                new DateTime($userData['lastlogin'])
            );
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
