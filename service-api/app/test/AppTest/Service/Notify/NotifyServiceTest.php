<?php


namespace AppTest\Service\Notify;

use App\Exception\BadRequestException;
use App\Service\Email\EmailClient;
use App\Service\Notify\NotifyService;
use Carbon\Carbon;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionClass;
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

    public function setUp()
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
    public function can_send_account_activation_email()
    {
        $emailTemplate = 'AccountActivationEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'parameter2' => 'http://localhost/activate-account/activate1234567890'
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_account_activation_email_and_exception_thrown()
    {
        $emailTemplate = 'AccountActivationEmail';
        $requestData = [
            'locale' => 'en_GB',
            'parameter2' => 'http://localhost/activate-account/activate1234567890'
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Recipient email address is missing to send an email');
        $result = $notify($requestData, $emailTemplate);
    }

    /** @test */
    public function can_send_account_activation_confirmation_email()
    {
        $emailTemplate = 'AccountActivatedConfirmationEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'parameter2' => 'http://localhost:9002/login'
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_account_activation_confirmation_email_throws_exception()
    {
        $emailTemplate = 'AccountActivatedConfirmationEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale'    => 'en_GB'
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Login link not set to send an email');
        $result = $notify($requestData, $emailTemplate);
    }

    /** @test */
    public function can_send_already_registered_email()
    {
        $emailTemplate = 'AlreadyRegisteredEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB'
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_already_registered_email_throws_exception()
    {
        $emailTemplate = 'AlreadyRegisteredEmail';
        $requestData = [];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Recipient email address is missing to send an email');
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function can_send_password_reset_email()
    {
        $emailTemplate = 'PasswordResetEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'parameter2' => 'http://localhost:9002/password-reset/passwordResetAABBCCDDEE'
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannnot_send_password_reset_email_throws_exception()
    {
        $emailTemplate = 'PasswordResetEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB'
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Password reset token not set to send an email');
        $result = $notify($requestData, $emailTemplate);
    }

    /** @test */
    public function can_send_password_changed_email()
    {
        $emailTemplate = 'PasswordChangedEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_password_changed_email_throws_exception()
    {
        $emailTemplate = 'PasswordChangedEmail';
        $requestData = [];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Recipient email address is missing to send an email');
        $result = $notify($requestData, $emailTemplate);
    }

    /** @test */
    public function can_send_request_change_email_to_current_email()
    {
        $emailTemplate = 'RequestChangeEmailToCurrentEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'parameter2' => 'new@email.com'
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_request_change_email_to_current_email_throws_exception()
    {
        $emailTemplate = 'RequestChangeEmailToCurrentEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('New email not set to send an email');
        $result = $notify($requestData, $emailTemplate);
    }

    /** @test */
    public function can_send_request_change_email_to_new_email()
    {
        $emailTemplate = 'RequestChangeEmailToNewEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
            'parameter2' => 'http://localhost:9002/verify-new-email/verifyNewEmailAABBCCDDEE'
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_request_change_email_to_new_email_throws_exception()
    {
        $emailTemplate = 'RequestChangeEmailToNewEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB'
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('New email path not set to send an email');
        $result = $notify($requestData, $emailTemplate);
    }

    /** @test */
    public function can_send_someone_tried_to_use_your_email_in_email_reset_request()
    {
        $emailTemplate = 'SomeoneTriedToUseYourEmailInEmailResetRequest';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_someone_tried_to_use_your_email_in_email_reset_request_throws_exception()
    {
        $emailTemplate = 'SomeoneTriedToUseYourEmailInEmailResetRequest';
        $requestData = [
            'locale' => 'en_GB'
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Recipient email address is missing to send an email');
        $result = $notify($requestData, $emailTemplate);
    }

    /** @test */
    public function can_send_activation_key_request_confirmation_mail()
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'referenceNumber' => '700000000138',
            'postcode' =>'HS8 2YB',
            'letterExpectedDate' => (new Carbon())->addWeeks(2)->format('j F Y'),
            'locale' => 'en-GB',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_activation_key_request_confirmation_mail_throws_exception()
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'referenceNumber' => '700000000138',
            'postcode' =>'HS8 2YB',
            'locale' => 'en-GB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Letter expected date not set to send an email');
        $result = $notify($requestData, $emailTemplate);
    }

    /** @test */
    public function can_send_activation_key_request_confirmation_email_when_lpa_needs_cleansing()
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
        $requestData = [
            'recipient' => 'test@test.com',
            'referenceNumber' => '700000000138',
            'letterExpectedDate' => (new Carbon())->addWeeks(2)->format('j F Y'),
            'locale' => 'en-GB',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_activation_key_request_confirmation_email_when_lpa_needs_cleansing_throws_exception()
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
        $requestData = [
            'recipient' => 'test@test.com',
            'letterExpectedDate' => (new Carbon())->addWeeks(2)->format('j F Y'),
            'locale' => 'en-GB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Reference number not set to send an email');
        $result = $notify($requestData, $emailTemplate);
    }

    /** @test */
    public function cannot_send_activation_key_request_confirmation_email_when_lpa_needs_cleansing()
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
        $requestData = [
            'recipient' => 'test@test.com',
            'referenceNumber' => '700000000138',
            'locale' => 'en-GB',
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Letter expected date not set to send an email');
        $result = $notify($requestData, $emailTemplate);
    }

    /** @test */
    public function can_send_no_account_exists_email()
    {
        $emailTemplate = 'NoAccountExistsEmail';
        $requestData = [
            'recipient' => 'test@test.com',
            'locale' => 'en_GB',
        ];
        $notify = $this->getNotifyService();
        $result = $notify($requestData, $emailTemplate);

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_send_no_account_exists_email_throws_exception()
    {
        $emailTemplate = 'NoAccountExistsEmail';
        $requestData = [
            'locale' => 'en_GB'
        ];
        $notify = $this->getNotifyService();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Recipient email address is missing to send an email');
        $result = $notify($requestData, $emailTemplate);
    }
}