<?php

declare(strict_types=1);

namespace CommonTest\Service\Email;

use Alphagov\Notifications\Client as NotifyClient;
use Common\Service\Email\EmailClient;
use PHPUnit\Framework\TestCase;

class EmailClientTest extends TestCase
{
    public function testSendAlreadyRegisteredEmail()
    {
        $notifyClientProphecy = $this->prophesize(NotifyClient::class);

        $recipient = 'a@b.com';

        $notifyClientProphecy->sendEmail($recipient, EmailClient::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED)
            ->shouldBeCalledOnce();

        $emailClient = new EmailClient($notifyClientProphecy->reveal());

        $emailClient->sendAlreadyRegisteredEmail($recipient);
    }
}
