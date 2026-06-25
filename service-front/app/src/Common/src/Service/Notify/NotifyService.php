<?php

declare(strict_types=1);

namespace Common\Service\Notify;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use Locale;
use Psr\Log\LoggerInterface;

class NotifyService
{
    private string $locale;

    public const string ACTIVATION_KEY_REQUEST_CONFIRMATION_EMAIL_TEMPLATE                     =
        'ActivationKeyRequestConfirmationEmail';
    public const string ACTIVATION_KEY_REQUEST_CONFIRMATION_LPA_NEEDS_CLEANSING_EMAIL_TEMPLATE =
        'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';

    public function __construct(
        private ApiClient $apiClient,
        private LoggerInterface $logger,
    ) {
        $this->locale = Locale::getDefault();
    }

    /**
     * Example usage:
     * sendEmailToUser('emailTemplate', 'recipient', activateAccountUrl: 'value1', signInLink: 'value2');
     *
     * @param string $emailTemplate
     * @param string $recipient
     * @param mixed  ...$emailData
     * @return bool
     * @throws ApiException
     */
    public function sendEmailToUser(
        string $emailTemplate,
        string $recipient,
        string ...$emailData,
    ): bool {
        $this->logger->debug(
            'Request to send user email',
            [
                'template' => $emailTemplate,
            ]
        );

        try {
            $this->apiClient->httpPost(
                '/v1/email-user/' . $emailTemplate,
                array_merge(
                    [
                        'recipient' => $recipient,
                        'locale'    => $this->locale,
                    ],
                    $emailData,
                )
            );

            $this->logger->debug(
                'Successfully sent email {email} to {recipient}',
                [
                    'email'     => $emailTemplate,
                    'recipient' => $recipient,
                ]
            );

            return true;
        } catch (ApiException $ex) {
            $this->logger->debug('Failed to send user email');
            throw $ex;
        }
    }
}
