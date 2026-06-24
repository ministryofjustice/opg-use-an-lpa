<?php

declare(strict_types=1);

namespace AppTest\Service\Notify;

use App\Exception\BadRequestException;
use App\Service\Email\EmailClient;
use App\Service\Notify\NotifyService;
use DateTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class NotifyServiceTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private EmailClient|ObjectProphecy $emailClientProphecy;

    public function setUp(): void
    {
        $this->loggerProphecy      = $this->prophesize(LoggerInterface::class);
        $this->emailClientProphecy = $this->prophesize(EmailClient::class);
    }

    private function getNotifyService(): NotifyService
    {
        return new NotifyService(
            $this->loggerProphecy->reveal(),
            $this->emailClientProphecy->reveal()
        );
    }

    #[Test]
    public function can_send_activation_key_request_confirmation_mail(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmail';
        $requestData   = [
            'recipient'          => 'test@test.com',
            'locale'             => 'en_GB',
            'referenceNumber'    => '700000000138',
            'postCode'           => 'HS8 2YB',
            'letterExpectedDate' => (new DateTime())->modify('+2 weeks')->format('j F Y'),
        ];
        $notify        = $this->getNotifyService();
        $result        = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    #[Test]
    public function cannot_send_activation_key_request_confirmation_mail_throws_exception(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmail';
        $requestData   = [
            'recipient'       => 'test@test.com',
            'locale'          => 'en_GB',
            'referenceNumber' => '700000000138',
            'postCode'        => 'HS8 2YB',
        ];
        $notify        = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    #[Test]
    public function cannot_send_activation_key_request_confirmation_mail_throws_exception_again(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmail';
        $requestData   = [
            'to'                 => 'test@test.com',
            'locale'             => 'en_GB',
            'referenceNumber'    => '700000000138',
            'postCode'           => 'HS8 2YB',
            'letterExpectedDate' => (new DateTime())->modify('+2 weeks')->format('j F Y'),
        ];
        $notify        = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameter not set to send an email');
        $result = $notify($emailTemplate, $requestData);
    }

    #[Test]
    public function can_send_activation_key_request_confirmation_email_when_lpa_needs_cleansing(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
        $requestData   = [
            'recipient'          => 'test@test.com',
            'locale'             => 'en_GB',
            'referenceNumber'    => '700000000138',
            'letterExpectedDate' => (new DateTime())->modify('+4 weeks')->format('j F Y'),
        ];
        $notify        = $this->getNotifyService();
        $result        = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    #[Test]
    public function cannot_send_activation_key_request_confirmation_email_lpa_needs_cleansing_throws_exception(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
        $requestData   = [
            'recipient'          => 'test@test.com',
            'locale'             => 'en_GB',
            'letterExpectedDate' => (new DateTime())->modify('+4 weeks')->format('j F Y'),
        ];
        $notify        = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    #[Test]
    public function cannot_send_activation_key_request_confirmation_email_when_lpa_needs_cleansing(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
        $requestData   = [
            'recipient'       => 'test@test.com',
            'locale'          => 'en_GB',
            'referenceNumber' => '700000000138',
        ];
        $notify        = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }
}
