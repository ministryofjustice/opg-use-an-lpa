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
    public function can_send_account_activation_email(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACCOUNT_ACTIVATION[EmailClient::EN_LOCALE],
            [
                'activate-account-url' => 'http://localhost:9002/activate-account/activateAccountAABBCCDDEE',
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendAccountActivationEmail(
            $recipient,
            $this->defaultLocale,
            'http://localhost:9002/activate-account/activateAccountAABBCCDDEE'
        );
    }

    #[Test]
    public function can_send_account_activated_confirmation_email(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACCOUNT_ACTIVATED_CONFIRMATION[EmailClient::EN_LOCALE],
            [
                'sign-in-url' => 'http://localhost:9002/login',
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendAccountActivatedConfirmationEmail(
            $recipient,
            $this->defaultLocale,
            'http://localhost:9002/login'
        );
    }

    #[Test]
    public function can_send_already_registered_email(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED[EmailClient::EN_LOCALE]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendAlreadyRegisteredEmail($recipient, $this->defaultLocale);
    }

    #[Test]
    public function can_send_password_reset_email(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_RESET[EmailClient::EN_LOCALE],
            [
                'password-reset-url' => 'http://localhost:9002/password-reset/passwordResetAABBCCDDEE',
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendPasswordResetEmail(
            $recipient,
            $this->defaultLocale,
            'http://localhost:9002/password-reset/passwordResetAABBCCDDEE'
        );
    }

    #[Test]
    public function can_send_password_change_email(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_CHANGE[EmailClient::EN_LOCALE]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendPasswordChangedEmail($recipient, $this->defaultLocale);
    }

    #[Test]
    public function can_send_change_email_to_current_email(): void
    {
        $recipient = 'current@email.com';
        $newEmail  = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL[EmailClient::EN_LOCALE],
            [
                'new-email-address' => $newEmail,
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendRequestChangeEmailToCurrentEmail($recipient, $this->defaultLocale, $newEmail);
    }

    #[Test]
    public function can_send_change_email_verify_to_new_email(): void
    {
        $recipient = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL[EmailClient::EN_LOCALE],
            [
                'verify-new-email-url' => 'http://localhost:9002/verify-new-email/verifyNewEmailAABBCCDDEE',
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendRequestChangeEmailToNewEmail(
            $recipient,
            $this->defaultLocale,
            'http://localhost:9002/verify-new-email/verifyNewEmailAABBCCDDEE'
        );
    }

    #[Test]
    public function can_send_someone_tried_to_use_your_email_for_email_change(): void
    {
        $recipient = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE[EmailClient::EN_LOCALE]
        )->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendSomeoneTriedToUseYourEmailInEmailResetRequest($recipient, $this->defaultLocale);
    }

    #[Test]
    public function can_send_account_activation_email_if_locale_is_cy(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACCOUNT_ACTIVATION[EmailClient::CY_LOCALE],
            [
                'activate-account-url' => 'http://localhost:9002/cy/activate-account/activateAccountAABBCCDDEE',
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendAccountActivationEmail(
            $recipient,
            self::CY_LOCALE,
            'http://localhost:9002/cy/activate-account/activateAccountAABBCCDDEE'
        );
    }

    #[Test]
    public function can_send_account_activated_confirmation_email_if_locale_is_cy(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACCOUNT_ACTIVATED_CONFIRMATION[EmailClient::CY_LOCALE],
            [
                'sign-in-url' => 'http://localhost:9002/cy/login',
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendAccountActivatedConfirmationEmail(
            $recipient,
            self::CY_LOCALE,
            'http://localhost:9002/cy/login'
        );
    }

    #[Test]
    public function can_send_already_registered_email_if_locale_is_cy(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED[EmailClient::CY_LOCALE]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendAlreadyRegisteredEmail($recipient, self::CY_LOCALE);
    }

    #[Test]
    public function can_send_password_reset_email_if_locale_is_cy(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_RESET[EmailClient::CY_LOCALE],
            [
                'password-reset-url' => 'http://localhost:9002/cy/password-reset/passwordResetAABBCCDDEE',
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendPasswordResetEmail(
            $recipient,
            self::CY_LOCALE,
            'http://localhost:9002/cy/password-reset/passwordResetAABBCCDDEE'
        );
    }

    #[Test]
    public function can_send_password_change_email_if_locale_is_cy(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_CHANGE[EmailClient::CY_LOCALE]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendPasswordChangedEmail($recipient, self::CY_LOCALE);
    }

    #[Test]
    public function can_send_change_email_to_current_email_if_locale_is_cy(): void
    {
        $recipient = 'current@email.com';
        $newEmail  = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL[EmailClient::CY_LOCALE],
            [
                'new-email-address' => $newEmail,
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendRequestChangeEmailToCurrentEmail($recipient, self::CY_LOCALE, $newEmail);
    }

    #[Test]
    public function can_send_change_email_verify_to_new_email_if_locale_is_cy(): void
    {
        $recipient = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL[EmailClient::CY_LOCALE],
            [
                'verify-new-email-url' => 'http://localhost:9002/cy/verify-new-email/verifyNewEmailAABBCCDDEE',
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendRequestChangeEmailToNewEmail(
            $recipient,
            self::CY_LOCALE,
            'http://localhost:9002/cy/verify-new-email/verifyNewEmailAABBCCDDEE'
        );
    }

    #[Test]
    public function can_send_someone_tried_to_use_your_email_for_email_change_if_locale_is_cy(): void
    {
        $recipient = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE[EmailClient::CY_LOCALE]
        )->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendSomeoneTriedToUseYourEmailInEmailResetRequest($recipient, self::CY_LOCALE);
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

    #[Test]
    public function cannot_send_account_activation_key_as_account_does_not_exist(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_NO_EXISTING_ACCOUNT[EmailClient::EN_LOCALE]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendNoAccountExistsEmail(
            $recipient,
            $this->defaultLocale
        );
    }

    #[Test]
    public function send_force_password_reset_email(): void
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_FORCE_PASSWORD_RESET[EmailClient::EN_LOCALE],
            [
                'password-reset-url' => 'http://localhost:9002/cy/password-reset/passwordResetAABBCCDDEE',
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal());

        $emailClient->sendForcePasswordResetEmail(
            $recipient,
            $this->defaultLocale,
            'http://localhost:9002/cy/password-reset/passwordResetAABBCCDDEE'
        );
    }
}
