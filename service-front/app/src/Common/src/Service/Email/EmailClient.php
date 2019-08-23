<?php

declare(strict_types=1);

namespace Common\Service\Email;

use Alphagov\Notifications\Client as NotifyClient;

/**
 * Class EmailClient
 * @package Common\Service\Email
 */
class EmailClient
{
    /**
     * Template IDs for the notify client
     */
    const TEMPLATE_ID_ACCOUNT_ACTIVATION               = 'd897fe13-a0c3-4c50-aa5b-3f0efacda5dc';
    const TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED = '4af9acf0-f2c1-4ecc-8441-0e2173890463';
    const TEMPLATE_ID_PASSWORD_RESET                   = 'd32af4a6-49ad-4338-a2c2-dcb5801a40fc';

    /**
     * @var NotifyClient
     */
    private $notifyClient;

    /**
     * EmailClient constructor.
     * @param NotifyClient $notifyClient
     */
    public function __construct(NotifyClient $notifyClient)
    {
        $this->notifyClient = $notifyClient;
    }

    /**
     * Send an account activation email to a user
     *
     * @param string $recipient
     * @param string $activateAccountUrl
     */
    public function sendAccountActivationEmail(string $recipient, string $activateAccountUrl)
    {
        $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_ACCOUNT_ACTIVATION, [
            'activate-account-url' => $activateAccountUrl,
        ]);
    }

    /**
     * Send an email to a user to tell them that the email address is already registered
     *
     * @param string $recipient
     */
    public function sendAlreadyRegisteredEmail(string $recipient)
    {
        $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED);
    }

    /**
     * Send a password reset request email to a user
     *
     * @param string $recipient A valid email address
     * @param string $passwordResetUrl
     */
    public function sendPasswordResetEmail(string $recipient, string $passwordResetUrl)
    {
        $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_PASSWORD_RESET, [
            'password-reset-url' => $passwordResetUrl,
        ]);
    }
}
