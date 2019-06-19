<?php

namespace Common\Service\User;

use Common\Service\ApiClient\Client as ApiClient;
use ArrayObject;

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
     * @throws \Http\Client\Exception
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
     * @throws \Http\Client\Exception
     */
    public function getByEmail(string $email) : array
    {
        return $this->apiClient->httpGet('/v1/user', [
            'email' => $email,
        ]);
    }
}
