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

    public const ACTIVATE_ACCOUNT_TEMPLATE                                              = 'AccountActivationEmail';
    public const ALREADY_REGISTERED_EMAIL_TEMPLATE                                      = 'AlreadyRegisteredEmail';
    public const ACCOUNT_ACTIVATION_CONFIRMATION_EMAIL_TEMPLATE                         =
        'AccountActivatedConfirmationEmail';
    public const PASSWORD_CHANGE_EMAIL_TEMPLATE                                         = 'PasswordChangedEmail';
    public const PASSWORD_RESET_EMAIL_TEMPLATE                                          = 'PasswordResetEmail';
    public const SOMEONE_TRIED_TO_USE_YOUR_EMAIL_IN_EMAIL_RESET_REQUEST_TEMPLATE        =
        'SomeoneTriedToUseYourEmailInEmailResetRequest';
    public const REQUEST_CHANGE_EMAIL_TO_NEW_EMAIL                                      =
        'RequestChangeEmailToNewEmail';
    public const REQUEST_CHANGE_EMAIL_TO_CURRENT_EMAIL                                  =
        'RequestChangeEmailToCurrentEmail';
    public const ACTIVATION_KEY_REQUEST_CONFIRMATION_EMAIL_TEMPLATE                     =
        'ActivationKeyRequestConfirmationEmail';
    public const ACTIVATION_KEY_REQUEST_CONFIRMATION_LPA_NEEDS_CLEANSING_EMAIL_TEMPLATE =
        'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
    public const NO_ACCOUNT_EXISTS_EMAIL_TEMPLATE                                       = 'NoAccountExistsEmail';
    public const FORCE_PASSWORD_RESET_EMAIL_TEMPLATE                                    = 'ForcePasswordResetEmail';

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
