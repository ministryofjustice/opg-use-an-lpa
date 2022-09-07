<?php

namespace AppTest\Service\Notify;

use App\Exception\BadRequestException;
use App\Service\Email\EmailClient;
use App\Service\Notify\NotifyService;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use DateTime;
use PHPUnit\Framework\TestCase;

class NotifyServiceTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    /**
     * @var ObjectProphecy
     */
    private $emailClientProphecy;

    public function setUp(): void
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->emailClientProphecy = $this->prophesize(EmailClient::class);
    }

    private function getNotifyService(): NotifyService
    {
        return new NotifyService(
            $this->loggerProphecy->reveal(),
            $this->emailClientProphecy->reveal()
        );
    }

    /** @test */
    public function can_send_account_activation_email(): void
    {
        $emailTemplate = 'AccountActivationEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'activateAccountUrl' => 'http://localhost/activate-account/activate1234567890',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_account_activation_email_and_exception_thrown(): void
    {
        $emailTemplate = 'AccountActivationEmail';
        $requestData = [
            'locale' => 'en_GB',
            'activateAccountUrl' => 'http://localhost/activate-account/activate1234567890',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function can_send_account_activation_confirmation_email(): void
    {
        $emailTemplate = 'AccountActivatedConfirmationEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'signInLink' => 'http://localhost:9002/login',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_account_activation_confirmation_email_throws_exception(): void
    {
        $emailTemplate = 'AccountActivatedConfirmationEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function can_send_already_registered_email(): void
    {
        $emailTemplate = 'AlreadyRegisteredEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_already_registered_email_throws_exception(): void
    {
        $emailTemplate = 'AlreadyRegisteredEmail';
        $requestData = [];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function can_send_password_reset_email(): void
    {
        $emailTemplate = 'PasswordResetEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'passwordResetUrl' => 'http://localhost:9002/password-reset/passwordResetAABBCCDDEE',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_password_reset_email_throws_exception(): void
    {
        $emailTemplate = 'PasswordResetEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function can_send_password_changed_email(): void
    {
        $emailTemplate = 'PasswordChangedEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_password_changed_email_throws_exception(): void
    {
        $emailTemplate = 'PasswordChangedEmail';
        $requestData = [];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function can_send_request_change_email_to_current_email(): void
    {
        $emailTemplate = 'RequestChangeEmailToCurrentEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'newEmailAddress' => 'new@email.com',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_request_change_email_to_current_email_throws_exception(): void
    {
        $emailTemplate = 'RequestChangeEmailToCurrentEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function can_send_request_change_email_to_new_email(): void
    {
        $emailTemplate = 'RequestChangeEmailToNewEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'completeEmailChangeUrl' => 'http://localhost:9002/verify-new-email/verifyNewEmailAABBCCDDEE',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_request_change_email_to_new_email_throws_exception(): void
    {
        $emailTemplate = 'RequestChangeEmailToNewEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function can_send_someone_tried_to_use_your_email_in_email_reset_request(): void
    {
        $emailTemplate = 'SomeoneTriedToUseYourEmailInEmailResetRequest';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_someone_tried_to_use_your_email_in_email_reset_request_throws_exception(): void
    {
        $emailTemplate = 'SomeoneTriedToUseYourEmailInEmailResetRequest';
        $requestData = [
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function can_send_activation_key_request_confirmation_mail(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'referenceNumber' => '700000000138',
            'postCode' => 'HS8 2YB',
            'letterExpectedDate' => (new DateTime())->modify('+2 weeks')->format('j F Y'),
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_activation_key_request_confirmation_mail_throws_exception(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'referenceNumber' => '700000000138',
            'postCode' => 'HS8 2YB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function cannot_send_activation_key_request_confirmation_mail_throws_exception_again(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmail';
        $requestData = [
            'to' => 'test@test.com',
            'locale' => 'en_GB',
            'referenceNumber' => '700000000138',
            'postCode' => 'HS8 2YB',
            'letterExpectedDate' => (new DateTime())->modify('+2 weeks')->format('j F Y'),
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameter not set to send an email');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function can_send_activation_key_request_confirmation_email_when_lpa_needs_cleansing(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'referenceNumber' => '700000000138',
            'letterExpectedDate' => (new DateTime())->modify('+6 weeks')->format('j F Y'),
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_activation_key_request_confirmation_email_lpa_needs_cleansing_throws_exception(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'letterExpectedDate' => (new DateTime())->modify('+6 weeks')->format('j F Y'),
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function cannot_send_activation_key_request_confirmation_email_when_lpa_needs_cleansing(): void
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'referenceNumber' => '700000000138',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }

    /** @test */
    public function can_send_no_account_exists_email(): void
    {
        $emailTemplate = 'NoAccountExistsEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($emailTemplate, $requestData);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_no_account_exists_email_throws_exception(): void
    {
        $emailTemplate = 'NoAccountExistsEmail';
        $requestData = [
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Parameters count do not match expected');
        $result = $notify($emailTemplate, $requestData);
    }
}
