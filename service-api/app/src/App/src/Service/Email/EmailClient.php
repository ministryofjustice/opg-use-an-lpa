<?php

declare(strict_types=1);

namespace App\Service\Email;

use Alphagov\Notifications\Client;


/**
 * Class EmailClient
 * @package App\Service\Email
 */
class EmailClient
{
    /**
     * Possible language choices
     */
    public const EN_LOCALE = 'en_GB';
    public const CY_LOCALE = 'cy_GB';

    /**
     * Template IDs
     */
    public const TEMPLATE_ID_ACCOUNT_ACTIVATION = [
        self::EN_LOCALE => 'd897fe13-a0c3-4c50-aa5b-3f0efacda5dc',
        self::CY_LOCALE => 'e0933491-b9ee-4552-adb3-7775843f4d4b',
    ];
    public const TEMPLATE_ID_PASSWORD_RESET = [
        self::EN_LOCALE => 'd32af4a6-49ad-4338-a2c2-dcb5801a40fc',
        self::CY_LOCALE => 'ea7ff73a-2a43-4f7e-a1e4-3e1351ae262d',
    ];
    public const TEMPLATE_ID_PASSWORD_CHANGE = [
        self::EN_LOCALE => '75080a89-7b22-4792-bdf6-6636467a7999',
        self::CY_LOCALE => 'e47fdf50-d223-4f26-a12f-417bd53b03dd',
    ];
    public const TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL = [
        self::EN_LOCALE => '19051f55-d60d-4bbc-ab49-cf85580d3102',
        self::CY_LOCALE => 'f06ab05a-af11-4047-bcbb-4a33d0673829',
    ];
    public const TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL = [
        self::EN_LOCALE => 'bcf7e3f7-7f76-4e0a-87ee-b6722bdc223a',
        self::CY_LOCALE => '0034dfdc-456b-4cea-8e0e-6915efcd91b2',
    ];
    public const TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE = [
        self::EN_LOCALE => '5a74677a-4840-49cf-a92b-f1de2b31cebb',
        self::CY_LOCALE => '0c2acaa0-96d6-4c01-a32d-f5d8a43ce392',
    ];
    public const TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED = [
        self::EN_LOCALE => '4af9acf0-f2c1-4ecc-8441-0e2173890463',
        self::CY_LOCALE => 'b9b32dd2-67e9-45e8-a454-4301ba049a81',
    ];
    public const TEMPLATE_ID_ACCOUNT_ACTIVATED_CONFIRMATION = [
        self::EN_LOCALE => 'c23501a2-4893-426b-85e6-8a8e3731ddd7',
        self::CY_LOCALE => '1be4d491-28df-4dfe-b90c-b285eafba05b',
    ];
    public const TEMPLATE_ID_ACTIVATION_KEY_REQUEST_CONFIRMATION = [
        self::EN_LOCALE => '4674b106-c9eb-4314-a68c-ea4ba78808c5',
        self::CY_LOCALE => '712625ce-241f-45d9-bb51-13b89f6c7748',
    ];
    public const TEMPLATE_ID_ACTIVATION_KEY_REQUEST_WHEN_LPA_NEEDS_CLEANSING = [
        self::EN_LOCALE => 'e88d7f4d-a6fb-4dfb-a8a0-8f1c3df52744',
        self::CY_LOCALE => '1abc3673-1764-48c5-a870-07e1064212d1',
    ];
    public const TEMPLATE_ID_NO_EXISTING_ACCOUNT = [
        self::EN_LOCALE => '36a86dbf-27a3-448c-a743-5f915e1733c3',
        self::CY_LOCALE => '4966311d-9abb-4b39-8403-0a4be36756e6',
    ];

    private Client $notifyClient;

    public function __construct(Client $notifyClient)
    {
        $this->notifyClient = $notifyClient;
    }

    /**
     * Email an account activation email to a user
     *
     * @param string $recipient
     * @param string $locale
     * @param string $activateAccountUrl
     */
    public function sendAccountActivationEmail(string $recipient, string $locale, string $activateAccountUrl): void
    {

        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_ACCOUNT_ACTIVATION[$locale],
            [
                'activate-account-url' => $activateAccountUrl,
            ]
        );
    }

    /**
     * Email an account activation confirmation email to a user
     *
     * @param string $recipient
     * @param string $locale
     * @param string $signInLink
     */
    public function sendAccountActivatedConfirmationEmail(string $recipient, string $locale, string $signInLink): void
    {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_ACCOUNT_ACTIVATED_CONFIRMATION[$locale],
            [
                'sign-in-url' => $signInLink,
            ]
        );
    }

    /**
     * Email a user to tell them that the email address is already registered
     *
     * @param string $recipient
     * @param string $locale
     */
    public function sendAlreadyRegisteredEmail(string $recipient, string $locale): void
    {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED[$locale]
        );
    }

    /**
     * Email a password reset request email to a user
     *
     * @param string $recipient A valid email address
     * @param string $locale
     * @param string $passwordResetUrl
     */
    public function sendPasswordResetEmail(string $recipient, string $locale, string $passwordResetUrl): void
    {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_PASSWORD_RESET[$locale],
            [
                'password-reset-url' => $passwordResetUrl,
            ]
        );
    }

    /**
     * Email a user to inform them that their password has changed
     *
     * @param string $recipient
     * @param string $locale
     */
    public function sendPasswordChangedEmail(string $recipient, string $locale): void
    {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_PASSWORD_CHANGE[$locale]
        );
    }

    /**
     * Email a user's current email informing them on how to complete their email reset
     *
     * @param string $recipient
     * @param string $locale
     * @param string $newEmailAddress
     */
    public function sendRequestChangeEmailToCurrentEmail(string $recipient, string $locale, string $newEmailAddress): void
    {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL[$locale],
            [
                'new-email-address' => $newEmailAddress,
            ]
        );
    }

    /**
     * Email the new email address the user selected to reset their email to
     *
     * @param string $recipient
     * @param string $locale
     * @param string $completeEmailChangeUrl
     */
    public function sendRequestChangeEmailToNewEmail(string $recipient, string $locale, string $completeEmailChangeUrl): void
    {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL[$locale],
            [
                'verify-new-email-url' => $completeEmailChangeUrl,
            ]
        );
    }

    /**
     * Email the new email address telling the user that someone has tried to use their email on the service
     *
     * @param string $recipient
     * @param string $locale
     */
    public function sendSomeoneTriedToUseYourEmailInEmailResetRequest(string $recipient, string $locale): void
    {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE[$locale]
        );
    }

    /**
     * Email an activation key request confirmation email to a user
     *
     * @param string $recipient
     * @param string $locale
     * @param string $referenceNumber
     * @param string $postCode
     * @param string $letterExpectedDate
     */
    public function sendActivationKeyRequestConfirmationEmail(
        string $recipient,
        string $locale,
        string $referenceNumber,
        string $postCode,
        string $letterExpectedDate
    ): void {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_CONFIRMATION[$locale],
            [
                'reference_number' => $referenceNumber,
                'postcode'         => $postCode,
                'date'             => $letterExpectedDate
            ]
        );
    }

    /**
     * Email an activation key request confirmation email to a user when LPA is identified not cleansed
     *
     * @param string $recipient
     * @param string $locale
     * @param string $referenceNumber
     * @param string $letterExpectedDate
     */
    public function sendActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing(
        string $recipient,
        string $locale,
        string $referenceNumber,
        string $letterExpectedDate
    ): void {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_WHEN_LPA_NEEDS_CLEANSING[$locale],
            [
                'reference_number'  => $referenceNumber,
                'date'              => $letterExpectedDate,
            ]
        );
    }

    /**
     * Email a user to inform them that no account exists under the email provided
     * @param string $locale
     * @param string $recipient
     */
    public function sendNoAccountExistsEmail(string $recipient, string $locale): void
    {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_NO_EXISTING_ACCOUNT[$locale]
        );
    }

}