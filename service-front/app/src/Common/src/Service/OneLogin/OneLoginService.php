<?php

declare(strict_types=1);

namespace Common\Service\OneLogin;

use Common\Service\ApiClient\Client as ApiClient;
use Facile\OpenIDClient\Session\AuthSession;

class OneLoginService
{
    public const OIDC_AUTH_INTERFACE = 'oidcauthinterface';

    public function __construct(private ApiClient $apiClient)
    {
    }

    public function authenticate(string $uiLocale, string $redirectUrl): ?array
    {
        return $this->apiClient->httpGet('/v1/auth/start', [
            'ui_locale'    => $uiLocale,
            'redirect_url' => $redirectUrl,
        ]);
    }

    public function callback(string $code, string $state, AuthSession $authCredentials): ?array
    {
        return $this->apiClient->httpPost('/v1/auth/callback', [
            'code'         => $code,
            'state'        => $state,
            'auth_session' => $authCredentials,
        ]);
    }
}
