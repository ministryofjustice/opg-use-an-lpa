<?php

declare(strict_types=1);

namespace Common\Service\Notify;

use Common\Service\ApiClient\Client as ApiClient;
use Psr\Log\LoggerInterface;
use Locale;

/**
 * Class NotifyService
 *
 * @package Common\Service\Notify
 */
class NotifyService
{
    private ApiClient $apiClient;
    private LoggerInterface $logger;
    private string $locale;

    public const ACTIVATE_ACCOUNT_TEMPLATE = 'AccountActivationEmail';
    public const ALREADY_REGISTERED_EMAIL_TEMPLATE = 'AlreadyRegisteredEmail';
    public const ACCOUNT_ACTIVATION_CONFIRMATION_EMAIL_TEMPLATE = 'AccountActivatedConfirmationEmail';
    public const PASSWORD_CHANGE_EMAIL_TEMPLATE = 'PasswordChangedEmail';
    public const PASSWORD_RESET_EMAIL_TEMPLATE = 'PasswordResetEmail';
    public const SOMEONE_TRIED_TO_USE_YOUR_EMAIL_IN_EMAIL_RESET_REQUEST_TEMPLATE =
        'SomeoneTriedToUseYourEmailInEmailResetRequest';
    public const REQUEST_CHANGE_EMAIL_TO_NEW_EMAIL = 'RequestChangeEmailToNewEmail';
    public const REQUEST_CHANGE_EMAIL_TO_CURRENT_EMAIL = 'RequestChangeEmailToCurrentEmail';
    public const ACTIVATION_KEY_REQUEST_CONFIRMATION_EMAIL_TEMPLATE = 'ActivationKeyRequestConfirmationEmail';
    public const ACTIVATION_KEY_REQUEST_CONFIRMATION_LPA_NEEDS_CLEANSING_EMAIL_TEMPLATE =
        'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
    public const NO_ACCOUNT_EXISTS_EMAIL_TEMPLATE = 'NoAccountExistsEmail';
    public const FORCE_PASSWORD_RESET_EMAIL_TEMPLATE = 'ForcePasswordResetEmail';

    /**
     * NotifyService constructor.
     *
     * @param ApiClient $apiClient
     */
    public function __construct(
        ApiClient $apiClient,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->locale = Locale::getDefault();
    }

    /**
     * @param string $emailTemplate
     * @param string $recipient
     * @param mixed  ...$emailData
     *
     * Example usage:
     * sendEmailToUser('emailTemplate', 'recipient', activateAccountUrl: 'value1', signInLink: 'value2');
     *
     * @return bool
     */
    public function sendEmailToUser(
        string $emailTemplate,
        string $recipient,
        string ...$emailData
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
                        'locale' => $this->locale,
                    ],
                    $emailData,
                )
            );

            $this->logger->notice('Successfully sent user email');
            return true;
        } catch (ApiException $ex) {
            $this->logger->notice('Failed to sent user email');
            throw $ex;
        }
    }
}
