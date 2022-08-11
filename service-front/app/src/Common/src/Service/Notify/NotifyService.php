<?php

declare(strict_types=1);

namespace Common\Service\Notify;

use Common\Service\ApiClient\Client as ApiClient;
use Psr\Log\LoggerInterface;
use Locale;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

/**
 * Class NotifyService
 * @package Common\Service\Notify
 */
class NotifyService
{
    private ApiClient $apiClient;
    private LoggerInterface $logger;
    private string $locale;

    /**
     * UserService constructor.
     *
     * @param ApiClient $apiClient
     * @param callable  $userModelFactory
     */
    public function __construct(
        ApiClient $apiClient,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        Locale::getDefault();
    }

    public function sendEmailToUser(
        string $recipient,
        ?string $parameter2,
        string $emailTemplate,
        ?string $referenceNumber ,
        ?string $postCode,
        ?string $letterExpectedDate
    ): void
    {
        $this->logger->debug('Request to send user email', [
            'template' => $emailTemplate
        ]);
        try {
            $this->apiClient->httpPost(
                '/v1/email-user/' . $emailTemplate,
                [
                    'recipient' => $recipient,
                    'locale' => locale_get_default(),
                    'parameter2' => $parameter2,
                    'referenceNumber' => $referenceNumber,
                    'postcode' => $postCode,
                    'letterExpectedDate' => $letterExpectedDate,
                ]
            );

            $this->logger->notice('Successfully sent user email');
        } catch (ApiException $ex) {
            $this->logger->notice('Failed to sent user email');
            throw $ex;
        }
    }
}
