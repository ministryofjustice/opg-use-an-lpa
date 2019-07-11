<?php

namespace Common\Service\User;

use Common\Entity\User;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use ArrayObject;
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
                $userData['firstname'],
                $userData['surname'],
                new \DateTime($userData['lastlogin'])
            );
        } catch (ApiException $e) {
            // TODO log or otherwise report authentication issue?
        } catch (Exception $e) {
            throw new RuntimeException("Marshaling user login datetime to DateTime failed", 500, $e);
        }

        return null;
    }
}
