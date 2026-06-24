<?php

declare(strict_types=1);

namespace App\Service\Email;

use Alphagov\Notifications\Client;

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
    public const TEMPLATE_ID_ACTIVATION_KEY_REQUEST_CONFIRMATION             = [
        self::EN_LOCALE => '4674b106-c9eb-4314-a68c-ea4ba78808c5',
        self::CY_LOCALE => '712625ce-241f-45d9-bb51-13b89f6c7748',
    ];
    public const TEMPLATE_ID_ACTIVATION_KEY_REQUEST_WHEN_LPA_NEEDS_CLEANSING = [
        self::EN_LOCALE => 'e88d7f4d-a6fb-4dfb-a8a0-8f1c3df52744',
        self::CY_LOCALE => '1abc3673-1764-48c5-a870-07e1064212d1',
    ];

    public function __construct(private Client $notifyClient)
    {
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
        string $letterExpectedDate,
    ): void {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_CONFIRMATION[$locale],
            [
                'reference_number' => $referenceNumber,
                'postcode'         => $postCode,
                'date'             => $letterExpectedDate,
            ],
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
        string $letterExpectedDate,
    ): void {
        $this->notifyClient->sendEmail(
            $recipient,
            self::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_WHEN_LPA_NEEDS_CLEANSING[$locale],
            [
                'reference_number' => $referenceNumber,
                'date'             => $letterExpectedDate,
            ],
        );
    }
}
