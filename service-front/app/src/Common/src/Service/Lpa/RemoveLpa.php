<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use App\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Psr\Log\LoggerInterface;

class RemoveLpa
{
    /** @var ApiClient */
    private $apiClient;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ApiClient       $apiClient
     * @param LoggerInterface $logger
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        ApiClient $apiClient,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    public function __invoke(string $userToken, string $actorLpaToken)
    {
        $this->apiClient->setUserTokenHeader($userToken);

        try {
            $lpaActorData = $this->apiClient->httpDelete('/v1/lpas/' . $actorLpaToken);
            if (isset($lpaActorData)) {
                $this->logger->notice(
                    'Successfully removed LPA for user lpa actor {token}',
                    [
                        'event_code' => EventCodes::LPA_REMOVED,
                        'token' => $actorLpaToken,
                    ]
                );
            }
        } catch (ApiException $ex) {
            $this->logger->notice(
                'Failed to remove LPA for user lpa actor {token}',
                [
                    'token' => $actorLpaToken,
                ]
            );
            throw $ex;
        }

        return (!is_null($lpaActorData) ? $lpaActorData : null);
    }
}
