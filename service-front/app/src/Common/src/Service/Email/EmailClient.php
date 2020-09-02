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
     * English template IDs for the notify client
     */
    const TEMPLATE_ID_ACCOUNT_ACTIVATION                      = 'd897fe13-a0c3-4c50-aa5b-3f0efacda5dc';
    const TEMPLATE_ID_PASSWORD_RESET                          = 'd32af4a6-49ad-4338-a2c2-dcb5801a40fc';
    const TEMPLATE_ID_PASSWORD_CHANGE                         = '75080a89-7b22-4792-bdf6-6636467a7999';
    const TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL      = '19051f55-d60d-4bbc-ab49-cf85580d3102';
    const TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL          = 'bcf7e3f7-7f76-4e0a-87ee-b6722bdc223a';
    const TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE  = '5a74677a-4840-49cf-a92b-f1de2b31cebb';
    const TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED        = '4af9acf0-f2c1-4ecc-8441-0e2173890463';

    /**
     * Welsh template IDs for the notify client
     */
    const WELSH_TEMPLATE_ID_ACCOUNT_ACTIVATION                      = 'e0933491-b9ee-4552-adb3-7775843f4d4b';
    const WELSH_TEMPLATE_ID_PASSWORD_RESET                          = 'ea7ff73a-2a43-4f7e-a1e4-3e1351ae262d';
    const WELSH_TEMPLATE_ID_PASSWORD_CHANGE                         = 'e47fdf50-d223-4f26-a12f-417bd53b03dd';
    const WELSH_TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL      = 'f06ab05a-af11-4047-bcbb-4a33d0673829';
    const WELSH_TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL          = '0034dfdc-456b-4cea-8e0e-6915efcd91b2';
    const WELSH_TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE  = '0c2acaa0-96d6-4c01-a32d-f5d8a43ce392';
    const WELSH_TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED        = 'b9b32dd2-67e9-45e8-a454-4301ba049a81';

    /**
     * @var NotifyClient
     */
    private $notifyClient;

    /**
     * EmailClient constructor.
     * @param NotifyClient $notifyClient
     */
    public function __construct(NotifyClient $notifyClient, string $locale)
    {
        $this->notifyClient = $notifyClient;
        $this->locale = $locale;
    }

    /**
     * Send an account activation email to a user
     *
     * @param string $recipient
     * @param string $activateAccountUrl
     */
    public function sendAccountActivationEmail(string $recipient, string $activateAccountUrl)
    {
        if ($this->locale === "cy") {
            $this->notifyClient->sendEmail($recipient, self::WELSH_TEMPLATE_ID_ACCOUNT_ACTIVATION, [
                'activate-account-url' => $activateAccountUrl,
            ]);
        } else {
            $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_ACCOUNT_ACTIVATION, [
                'activate-account-url' => $activateAccountUrl,
            ]);
        }
    }

    /**
     * Send an email to a user to tell them that the email address is already registered
     *
     * @param string $recipient
     */
    public function sendAlreadyRegisteredEmail(string $recipient)
    {
        if ($this->locale === "cy") {
            $this->notifyClient->sendEmail($recipient, self::WELSH_TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED);
        } else {
            $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED);
        }
    }

    /**
     * Send a password reset request email to a user
     *
     * @param string $recipient A valid email address
     * @param string $passwordResetUrl
     */
    public function sendPasswordResetEmail(string $recipient, string $passwordResetUrl)
    {
        if ($this->locale === "cy") {
            $this->notifyClient->sendEmail($recipient, self::WELSH_TEMPLATE_ID_PASSWORD_RESET, [
                'password-reset-url' => $passwordResetUrl,
            ]);
        } else {
            $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_PASSWORD_RESET, [
                'password-reset-url' => $passwordResetUrl,
            ]);
        }
    }

    /**
     * Send an email to a user to inform them that their password has changed
     *
     * @param string $recipient
     */
    public function sendPasswordChangedEmail(string $recipient)
    {
        if ($this->locale === "cy") {
            $this->notifyClient->sendEmail($recipient, self::WELSH_TEMPLATE_ID_PASSWORD_CHANGE);
        } else {
            $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_PASSWORD_CHANGE);
        }
    }

    /**
     * Send an email to a user's current email informing them on how to complete their email reset
     *
     * @param string $recipient
     * @param string $newEmailAddress
     */
    public function sendRequestChangeEmailToCurrentEmail(string $recipient, string $newEmailAddress)
    {
        if ($this->locale === "cy") {
            $this->notifyClient->sendEmail($recipient, self::WELSH_TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL, [
                'new-email-address' => $newEmailAddress,
            ]);
        } else {
            $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL, [
                'new-email-address' => $newEmailAddress,
            ]);
        }
    }

    /**
     * Send an email to the new email address the user selected to reset their email to
     *
     * @param string $recipient
     * @param string $completeEmailChangeUrl
     */
    public function sendRequestChangeEmailToNewEmail(string $recipient, string $completeEmailChangeUrl)
    {
        if ($this->locale === "cy") {
            $this->notifyClient->sendEmail($recipient, self::WELSH_TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL, [
                'verify-new-email-url' => $completeEmailChangeUrl,
            ]);
        } else {
            $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL, [
                'verify-new-email-url' => $completeEmailChangeUrl,
            ]);
        }
    }

    /**
     * Send an email to the new email address telling the user that someone has tried to use their email on the service
     *
     * @param string $recipient
     */
    public function sendSomeoneTriedToUseYourEmailInEmailResetRequest(string $recipient)
    {
        if ($this->locale === "cy") {
            $this->notifyClient->sendEmail($recipient, self::WELSH_TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE);
        } else {
            $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE);
        }
    }
}
