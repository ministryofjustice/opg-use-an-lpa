<?php

declare(strict_types=1);

namespace CommonTest\Service\Email;

use Alphagov\Notifications\Client as NotifyClient;
use Common\Service\Email\EmailClient;
use PHPUnit\Framework\TestCase;

class EmailClientTest extends TestCase
{
    /**
     * @var NotifyClient
     */
    private $notifyClientProphecy;
    /**
     * @var string
     */
    private $locale;

    public function setUp()
    {
        $this->notifyClientProphecy = $this->prophesize(NotifyClient::class);
        $this->locale = "en_GB";
    }

    /** @test */
    public function can_send_account_activation_email()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACCOUNT_ACTIVATION,
            [
                'activate-account-url' => 'http://localhost:9002/activate-account/activateAccountAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->locale);

        $emailClient->sendAccountActivationEmail($recipient, 'http://localhost:9002/activate-account/activateAccountAABBCCDDEE');
    }

    /** @test */
    public function can_send_already_registered_email()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail($recipient, EmailClient::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED)
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->locale);

        $emailClient->sendAlreadyRegisteredEmail($recipient);
    }

    /** @test */
    public function can_send_password_reset_email()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_RESET,
            [
                'password-reset-url' => 'http://localhost:9002/password-reset/passwordResetAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->locale);

        $emailClient->sendPasswordResetEmail($recipient, 'http://localhost:9002/password-reset/passwordResetAABBCCDDEE');
    }

    /** @test */
    public function can_send_password_change_email()
    {
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_CHANGE
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->locale);

        $emailClient->sendPasswordChangedEmail($recipient);
    }

    /** @test */
    public function can_send_change_email_to_current_email()
    {
        $recipient = 'current@email.com';
        $newEmail = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL,
            [
                'new-email-address' => $newEmail
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->locale);

        $emailClient->sendRequestChangeEmailToCurrentEmail($recipient, $newEmail);
    }

    /** @test */
    public function can_send_change_email_verify_to_new_email()
    {
        $recipient = 'new@email.com';

        $this->notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL,
            [
                'verify-new-email-url' => 'http://localhost:9002/verify-new-email/verifyNewEmailAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->locale);

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
            EmailClient::TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE
        )->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->locale);

        $emailClient->sendSomeoneTriedToUseYourEmailInEmailResetRequest($recipient);
    }

    /** @test */
    public function its_sends_a_welsh_email_if_locale_is_cy()
    {
        $this->locale = "cy";
        $recipient = 'a@b.com';

        $this->notifyClientProphecy->sendEmail($recipient, EmailClient::WELSH_TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED)
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($this->notifyClientProphecy->reveal(), $this->locale);

        $emailClient->sendAlreadyRegisteredEmail($recipient);
    }
}
