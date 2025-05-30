<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Psr\Log\LoggerInterface;
use ArrayObject;

class RemoveLpa
{
    /**
     * @param ApiClient       $apiClient
     * @param LoggerInterface $logger
     * @codeCoverageIgnore
     */
    public function __construct(
        private ApiClient $apiClient,
        private LoggerInterface $logger
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

        /*
        // TODO UML-3913 ingest the array [replace array object] using object Hydrator library here
        */
        return new ArrayObject($removedLpaData, ArrayObject::ARRAY_AS_PROPS);
    }
}
