<?php

declare(strict_types=1);

namespace Common\Service\SystemMessage;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;

class SystemMessageService
{
    public function __construct(
        private ApiClient $apiClient,
    ) {
    }

    /**
     * @throws ApiException
     */
    public function getMessages(): array
    {
        return $this->apiClient->httpGet(
            '/v1/system-message',
        );
    }
}
