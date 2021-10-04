<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Common\Service\Log\EventCodes;
use Common\Service\Lpa\Response\Parse\ParseActivationKeyExistsResponse;
use Common\Service\Lpa\Response\Parse\ParseLpaAlreadyAddedResponse;
use Common\Service\Lpa\Response\Parse\ParseOlderLpaMatchResponse;
use DateTimeInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class AddOlderLpa
 *
 * Single action invokeable class that is responsible for calling the APIs necessary to add older
 * LPAs to a users account.
 *
 * @package Common\Service\Lpa
 */
class CleanseLpa
{

    /** @var ApiClient */
    private ApiClient $apiClient;
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * AddOlderLpa constructor.
     *
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

    public function cleanse(
        string $userToken,
        int $lpaUid,
        ?int $actorId,
        string $additionalInformation
    ): OlderLpaApiResponse {
        $data = [
            'reference_number' => $lpaUid,
            'notes' => $additionalInformation
        ];

        if ($actorId !== null) {
            $data['actor_id'] = $actorId;
        }

        $this->apiClient->setUserTokenHeader($userToken);

        $response = $this->apiClient->httpPost('/v1/add-lpa/cleanse', $data);

        return new OlderLpaApiResponse(OlderLpaApiResponse::SUCCESS, $response);
    }
}
