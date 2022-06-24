<?php

declare(strict_types=1);

namespace CommonTest\Service\Email;

use Alphagov\Notifications\Client as NotifyClient;
use Carbon\Carbon;
use Common\Service\Email\EmailClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class EmailClientTest extends TestCase
{
    private ObjectProphecy|NotifyClient $notifyClientProphecy;

    private string $defaultLocale;

    private const EN_LOCALE = 'en_GB';
    private const CY_LOCALE = 'cy_GB';

    public function setUp(): void
    {
        $this->notifyClientProphecy = $this->prophesize(NotifyClient::class);
        $this->defaultLocale = self::EN_LOCALE;
    }

    /** @test */
    public function can_send_account_activation_email()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACCOUNT_ACTIVATION[EmailClient::EN_LOCALE],
            [
                'activate-account-url' => 'http://localhost:9002/activate-account/activateAccountAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->defaultLocale);

        $emailClient->sendAccountActivationEmail($recipient, 'http://localhost:9002/activate-account/activateAccountAABBCCDDEE');
    }

    /** @test */
    public function can_send_account_activated_confirmation_email()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACCOUNT_ACTIVATED_CONFIRMATION[EmailClient::EN_LOCALE],
            [
                'sign-in-url' => 'http://localhost:9002/login'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->defaultLocale);

        $emailClient->sendAccountActivatedConfirmationEmail($recipient, 'http://localhost:9002/login');
    }

    /** @test */
    public function can_send_already_registered_email()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED[EmailClient::EN_LOCALE]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->defaultLocale);

        $emailClient->sendAlreadyRegisteredEmail($recipient);
    }

    /** @test */
    public function can_send_password_reset_email()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_RESET[EmailClient::EN_LOCALE],
            [
                'password-reset-url' => 'http://localhost:9002/password-reset/passwordResetAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->defaultLocale);

        $emailClient->sendPasswordResetEmail($recipient, 'http://localhost:9002/password-reset/passwordResetAABBCCDDEE');
    }

    /** @test */
    public function can_send_password_change_email()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_CHANGE[EmailClient::EN_LOCALE]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->defaultLocale);

        $emailClient->sendPasswordChangedEmail($recipient);
    }

    /** @test */
    public function can_send_change_email_to_current_email()
    {
        $recipient = 'current@email.com';
        $newEmail = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL[EmailClient::EN_LOCALE],
            [
                'new-email-address' => $newEmail
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->defaultLocale);

        $emailClient->sendRequestChangeEmailToCurrentEmail($recipient, $newEmail);
    }

    /** @test */
    public function can_send_change_email_verify_to_new_email()
    {
        $recipient = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL[EmailClient::EN_LOCALE],
            [
                'verify-new-email-url' => 'http://localhost:9002/verify-new-email/verifyNewEmailAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->defaultLocale);

        $emailClient->sendRequestChangeEmailToNewEmail(
            $recipient,
            'http://localhost:9002/verify-new-email/verifyNewEmailAABBCCDDEE'
        );
    }

    /** @test */
    public function can_send_someone_tried_to_use_your_email_for_email_change()
    {
        $recipient = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE[EmailClient::EN_LOCALE]
        )->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->defaultLocale);

        $emailClient->sendSomeoneTriedToUseYourEmailInEmailResetRequest($recipient);
    }

    /** @test */
    public function can_send_account_activation_email_if_locale_is_cy()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACCOUNT_ACTIVATION[EmailClient::CY_LOCALE],
            [
                'activate-account-url' => 'http://localhost:9002/cy/activate-account/activateAccountAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), self::CY_LOCALE);

        $emailClient->sendAccountActivationEmail($recipient, 'http://localhost:9002/cy/activate-account/activateAccountAABBCCDDEE');
    }

    /** @test */
    public function can_send_account_activated_confirmation_email_if_locale_is_cy()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACCOUNT_ACTIVATED_CONFIRMATION[EmailClient::CY_LOCALE],
            [
                'sign-in-url' => 'http://localhost:9002/cy/login'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), self::CY_LOCALE);

        $emailClient->sendAccountActivatedConfirmationEmail($recipient, 'http://localhost:9002/cy/login');
    }

    /** @test */
    public function can_send_already_registered_email_if_locale_is_cy()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED[EmailClient::CY_LOCALE]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), self::CY_LOCALE);

        $emailClient->sendAlreadyRegisteredEmail($recipient);
    }

    /** @test */
    public function can_send_password_reset_email_if_locale_is_cy()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_RESET[EmailClient::CY_LOCALE],
            [
                'password-reset-url' => 'http://localhost:9002/cy/password-reset/passwordResetAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), self::CY_LOCALE);

        $emailClient->sendPasswordResetEmail($recipient, 'http://localhost:9002/cy/password-reset/passwordResetAABBCCDDEE');
    }

    /** @test */
    public function can_send_password_change_email_if_locale_is_cy()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_CHANGE[EmailClient::CY_LOCALE]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), self::CY_LOCALE);

        $emailClient->sendPasswordChangedEmail($recipient);
    }

    /** @test */
    public function can_send_change_email_to_current_email_if_locale_is_cy()
    {
        $recipient = 'current@email.com';
        $newEmail = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL[EmailClient::CY_LOCALE],
            [
                'new-email-address' => $newEmail
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), self::CY_LOCALE);

        $emailClient->sendRequestChangeEmailToCurrentEmail($recipient, $newEmail);
    }

    /** @test */
    public function can_send_change_email_verify_to_new_email_if_locale_is_cy()
    {
        $recipient = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL[EmailClient::CY_LOCALE],
            [
                'verify-new-email-url' => 'http://localhost:9002/cy/verify-new-email/verifyNewEmailAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), self::CY_LOCALE);

        $emailClient->sendRequestChangeEmailToNewEmail(
            $recipient,
            'http://localhost:9002/cy/verify-new-email/verifyNewEmailAABBCCDDEE'
        );
    }

    /** @test */
    public function can_send_someone_tried_to_use_your_email_for_email_change_if_locale_is_cy()
    {
        $recipient = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE[EmailClient::CY_LOCALE]
        )->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), self::CY_LOCALE);

        $emailClient->sendSomeoneTriedToUseYourEmailInEmailResetRequest($recipient);
    }

    /** @test */
    public function can_send_account_activation_key_request_confirmation_email_if_locale_is_cy()
    {
        $recipient = 'a@b.com';
        $referenceNumber = '700000000138';
        $postCode = 'HS8 2YB';
        $letterExpectedDate = (new Carbon())->addWeeks(2)->format('j F Y');

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_CONFIRMATION[EmailClient::CY_LOCALE],
            [
                'reference_number' => $referenceNumber,
                'postcode'         => $postCode,
                'date'             => $letterExpectedDate
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), self::CY_LOCALE);

        $emailClient->sendActivationKeyRequestConfirmationEmail($recipient, $referenceNumber, $postCode, $letterExpectedDate);
    }

    /** @test */
    public function can_send_account_activation_key_request_confirmation_email()
    {
        $recipient = 'a@b.com';
        $referenceNumber = '700000000138';
        $postCode = 'HS8 2YB';
        $letterExpectedDate = (new Carbon())->addWeeks(2)->format('j F Y');

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_CONFIRMATION[EmailClient::EN_LOCALE],
            [
                'reference_number' => $referenceNumber,
                'postcode'         => $postCode,
                'date'             => $letterExpectedDate
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->defaultLocale);

        $emailClient->sendActivationKeyRequestConfirmationEmail($recipient, $referenceNumber, $postCode, $letterExpectedDate);
    }

    /** @test */
    public function can_send_account_activation_key_request_confirmation_email_when_lpa_needs_cleansing()
    {
        $recipient = 'a@b.com';
        $referenceNumber = '700000000138';
        $letterExpectedDate = (new Carbon())->addWeeks(6)->format('j F Y');

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_WHEN_LPA_NEEDS_CLEANSING[EmailClient::EN_LOCALE],
            [
                'reference_number' => $referenceNumber,
                'date'             => $letterExpectedDate
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->defaultLocale);

        $emailClient->sendActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing(
            $recipient,
            $referenceNumber,
            $letterExpectedDate
        );
    }

    /** @test */
    public function can_send_account_activation_key_request_confirmation_email_when_lpa_needs_cleanse_if_locale_is_cy()
    {
        $recipient = 'a@b.com';
        $referenceNumber = '700000000138';
        $letterExpectedDate = (new Carbon())->addWeeks(6)->format('j F Y');

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACTIVATION_KEY_REQUEST_WHEN_LPA_NEEDS_CLEANSING[EmailClient::CY_LOCALE],
            [
                'reference_number' => $referenceNumber,
                'date'             => $letterExpectedDate
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), self::CY_LOCALE);

        $emailClient->sendActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing(
            $recipient,
            $referenceNumber,
            $letterExpectedDate
        );
    }
}
