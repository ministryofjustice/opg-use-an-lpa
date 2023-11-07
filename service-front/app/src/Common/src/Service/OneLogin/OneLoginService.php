<?php

declare(strict_types=1);

namespace Common\Service\OneLogin;

use Common\Service\ApiClient\Client as ApiClient;

class OneLoginService
{
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
}
