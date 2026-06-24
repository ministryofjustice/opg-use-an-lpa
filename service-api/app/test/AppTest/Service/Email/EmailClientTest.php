<?php

declare(strict_types=1);

namespace AppTest\Service\Email;

use Alphagov\Notifications\Client as NotifyClient;
use App\Service\Email\EmailClient;
use DateTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class EmailClientTest extends TestCase
{
    use ProphecyTrait;

    private NotifyClient|ObjectProphecy $notifyClientProphecy;

    private string $defaultLocale;

    private const EN_LOCALE = 'en_GB';
    private const CY_LOCALE = 'cy_GB';

    public function setUp(): void
    {
        $this->notifyClientProphecy = $this->prophesize(NotifyClient::class);
        $this->defaultLocale        = self::EN_LOCALE;
    }

    #[Test]
    public function can_send_account_activation_key_request_confirmation_email_if_locale_is_cy(): void
    {
        $recipient          = 'a@b.com';
        $referenceNumber    = '700000000138';
        $postCode           = 'HS8 2YB';
        $letterExpectedDate = (new DateTime())->modify('+2 weeks')->format('j F Y');

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_CONFIRMATION[EmailClient::CY_LOCALE],
            [
                'reference_number' => $referenceNumber,
                'postcode'         => $postCode,
                'date'             => $letterExpectedDate,
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendActivationKeyRequestConfirmationEmail(
            $recipient,
            self::CY_LOCALE,
            $referenceNumber,
            $postCode,
            $letterExpectedDate
        );
    }

    #[Test]
    public function can_send_account_activation_key_request_confirmation_email(): void
    {
        $recipient          = 'a@b.com';
        $referenceNumber    = '700000000138';
        $postCode           = 'HS8 2YB';
        $letterExpectedDate = (new DateTime())->modify('+2 weeks')->format('j F Y');

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_CONFIRMATION[EmailClient::EN_LOCALE],
            [
                'reference_number' => $referenceNumber,
                'postcode'         => $postCode,
                'date'             => $letterExpectedDate,
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendActivationKeyRequestConfirmationEmail(
            $recipient,
            $this->defaultLocale,
            $referenceNumber,
            $postCode,
            $letterExpectedDate
        );
    }

    #[Test]
    public function can_send_account_activation_key_request_confirmation_email_when_lpa_needs_cleansing(): void
    {
        $recipient          = 'a@b.com';
        $referenceNumber    = '700000000138';
        $letterExpectedDate = (new DateTime())->modify('+4 weeks')->format('j F Y');

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_WHEN_LPA_NEEDS_CLEANSING[EmailClient::EN_LOCALE],
            [
                'reference_number' => $referenceNumber,
                'date'             => $letterExpectedDate,
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing(
            $recipient,
            $this->defaultLocale,
            $referenceNumber,
            $letterExpectedDate
        );
    }

    #[Test]
    public function can_send_account_activation_key_request_confirmation_email_when_lpa_needs_cleanse_if_locale_is_cy(): void
    {
        $recipient          = 'a@b.com';
        $referenceNumber    = '700000000138';
        $letterExpectedDate = (new DateTime())->modify('+4 weeks')->format('j F Y');

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_WHEN_LPA_NEEDS_CLEANSING[EmailClient::CY_LOCALE],
            [
                'reference_number' => $referenceNumber,
                'date'             => $letterExpectedDate,
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing(
            $recipient,
            self::CY_LOCALE,
            $referenceNumber,
            $letterExpectedDate
        );
    }
}
