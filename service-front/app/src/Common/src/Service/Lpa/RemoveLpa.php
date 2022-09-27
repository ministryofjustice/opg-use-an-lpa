<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Psr\Log\LoggerInterface;

class RemoveLpa
{
    /**
     * @param ApiClient       $apiClient
     * @param LoggerInterface $logger
     * @param ParseLpaData    $parseLpaData
     * @codeCoverageIgnore
     */
    public function __construct(
        private ApiClient $apiClient,
        private LoggerInterface $logger,
        private ParseLpaData $parseLpaData,
    ) {
    }

    public function __invoke(string $userToken, string $actorLpaToken)
    {
        $this->apiClient->setUserTokenHeader($userToken);

        try {
            $removedLpaData = $this->apiClient->httpDelete('/v1/lpas/' . $actorLpaToken);
            $this->logger->notice(
                'Successfully removed LPA for user lpa actor {token}',
                [
                    'event_code' => EventCodes::LPA_REMOVED,
                    'token'      => $actorLpaToken,
                ]
            );
        } catch (ApiException $ex) {
            $this->logger->notice(
                'Failed to remove LPA for user lpa actor {token}',
                [
                    'token' => $actorLpaToken,
                ]
            );
            throw $ex;
        }

        return ($this->parseLpaData)($removedLpaData);
    }
}
