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
}
