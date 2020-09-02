<?php

declare(strict_types=1);

namespace CommonTest\Service\Email;

use Alphagov\Notifications\Client as NotifyClient;
use Common\Service\Email\EmailClient;
use PHPUnit\Framework\TestCase;

class EmailClientTest extends TestCase
{
    /** @test */
    public function can_send_account_activation_email()
    {
        $notifyClientProphecy = $this->prophesize(NotifyClient::class);

        $recipient = 'a@b.com';

        $notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_ACCOUNT_ACTIVATION,
            [
                'activate-account-url' => 'http://localhost:9002/activate-account/activateAccountAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($notifyClientProphecy->reveal());

        $emailClient->sendAccountActivationEmail($recipient, 'http://localhost:9002/activate-account/activateAccountAABBCCDDEE');
    }

    /** @test */
    public function can_send_already_registered_email()
    {
        $notifyClientProphecy = $this->prophesize(NotifyClient::class);

        $recipient = 'a@b.com';

        $notifyClientProphecy->sendEmail($recipient, EmailClient::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED)
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($notifyClientProphecy->reveal());

        $emailClient->sendAlreadyRegisteredEmail($recipient);
    }

    /** @test */
    public function can_send_password_reset_email()
    {
        $notifyClientProphecy = $this->prophesize(NotifyClient::class);

        $recipient = 'a@b.com';

        $notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_RESET,
            [
                'password-reset-url' => 'http://localhost:9002/password-reset/passwordResetAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($notifyClientProphecy->reveal());

        $emailClient->sendPasswordResetEmail($recipient, 'http://localhost:9002/password-reset/passwordResetAABBCCDDEE');
    }

    /** @test */
    public function can_send_password_change_email()
    {
        $notifyClientProphecy = $this->prophesize(NotifyClient::class);

        $recipient = 'a@b.com';

        $notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_PASSWORD_CHANGE
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($notifyClientProphecy->reveal());

        $emailClient->sendPasswordChangedEmail($recipient);
    }

    /** @test */
    public function can_send_change_email_to_current_email()
    {
        $notifyClientProphecy = $this->prophesize(NotifyClient::class);

        $recipient = 'current@email.com';
        $newEmail = 'new@email.com';

        $notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_CURRENT_EMAIL,
            [
                'new-email-address' => $newEmail
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($notifyClientProphecy->reveal());

        $emailClient->sendRequestChangeEmailToCurrentEmail($recipient, $newEmail);
    }

    /** @test */
    public function can_send_change_email_verify_to_new_email()
    {
        $notifyClientProphecy = $this->prophesize(NotifyClient::class);

        $recipient = 'new@email.com';

        $notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_EMAIL_CHANGE_SENT_TO_NEW_EMAIL,
            [
                'verify-new-email-url' => 'http://localhost:9002/verify-new-email/verifyNewEmailAABBCCDDEE'
            ]
        )
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($notifyClientProphecy->reveal());

        $emailClient->sendRequestChangeEmailToNewEmail(
            $recipient,
            'http://localhost:9002/verify-new-email/verifyNewEmailAABBCCDDEE'
        );
    }

    /** @test */
    public function can_send_someone_tried_to_use_your_email_for_email_change()
    {
        $notifyClientProphecy = $this->prophesize(NotifyClient::class);

        $recipient = 'new@email.com';

        $notifyClientProphecy->sendEmail(
            $recipient,
            EmailClient::TEMPLATE_ID_RESET_CONFLICT_EMAIL_CHANGE_INCOMPLETE
        )->shouldBeCalledOnce();

        $emailClient = new EmailClient($notifyClientProphecy->reveal());

        $emailClient->sendSomeoneTriedToUseYourEmailInEmailResetRequest($recipient);
    }
}
