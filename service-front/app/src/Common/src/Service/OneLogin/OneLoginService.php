<?php

declare(strict_types=1);

namespace Common\Service\OneLogin;

use Common\Service\ApiClient\Client as ApiClient;
use Facile\OpenIDClient\Session\AuthSession;
use Mezzio\Authentication\UserInterface;
use Psr\Log\LoggerInterface;

class OneLoginService
{
    public const OIDC_AUTH_INTERFACE = 'oidcauthinterface';

    /**
     * @var callable
     */
    private $userModelFactory;

    public function __construct(
        private ApiClient $apiClient,
        callable $userModelFactory,
        private LoggerInterface $logger,
    ) {
        $this->userModelFactory = function (
            string $identity,
            array $roles = [],
            array $details = [],
        ) use ($userModelFactory): UserInterface {
            return $userModelFactory($identity, $roles, $details);
        };
    }

    public function authenticate(string $uiLocale, string $redirectUrl): ?array
    {
        return $this->apiClient->httpGet('/v1/auth/start', [
            'ui_locale'    => $uiLocale,
            'redirect_url' => $redirectUrl,
        ]);
    }

    public function callback(string $code, string $state, AuthSession $authCredentials): ?UserInterface
    {
        $userData = $this->apiClient->httpPost('/v1/auth/callback', [
            'code'         => $code,
            'state'        => $state,
            'auth_session' => $authCredentials,
        ]);

        $this->logger->info(
            'Authentication successful for account with Id {id}',
            [
                'id'         => $userData['Id'],
                'last-login' => $userData['LastLogin'] ?? 'never',
            ]
        );

        $filteredDetails = [
            'Email'   => $userData['Email'],
            'Subject' => $userData['Identity'],
        ];

        if (array_key_exists('LastLogin', $userData)) {
            $filteredDetails['LastLogin'] = $userData['LastLogin'];
        }

        return ($this->userModelFactory)(
            $userData['Id'],
            [],
            $filteredDetails
        );
    }
}
