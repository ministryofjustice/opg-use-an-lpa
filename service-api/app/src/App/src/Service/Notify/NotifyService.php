<?php

declare(strict_types=1);

namespace App\Service\Notify;

use Alphagov\Notifications\Client;
use App\Exception\BadRequestException;
use App\Service\Email\EmailClient;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Class NotifyService
 *
 * Single action invokable class that validates parameters send to email client
 *
 * @package App\Service\Notify
 */
class NotifyService
{

    public function __construct(LoggerInterface $logger, EmailClient $emailClient)
    {
        $this->logger = $logger;
        $this->emailClient = $emailClient;
    }

    /**
     * @param array $requestData email parameters
     * @param EmailClient $emailClient
     * @param string $emailTemplate
     * @throws Exception Thrown when parameters do not match
     */
    public function __invoke(array $requestData, string $emailTemplate): void
    {
        $this->logger->debug('Am in Notify service in API.....{data}', ['data' => $requestData]);

        //Instantiate the reflection object
        $reflector = new ReflectionClass($this->emailClient);
        $properties = $reflector->getMethods();

        $i =1;
        //Now go through the $properties array and populate each property
        foreach($properties as $property)
        {
            //check client property name contains the template name passed
            if ($emailTemplate === 'AccountActivationEmail' && str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->debug('Sending account activation email to user');

                    $this->emailClient->sendAccountActivationEmail(
                        $requestData['recipient'],
                        $requestData['locale'],
                        $requestData['parameter2']
                    );
                }
            }
            if ($emailTemplate === 'AccountActivatedConfirmationEmail' && str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->debug('Sending account activation confirmation email to user');

                    $this->emailClient->sendAccountActivatedConfirmationEmail(
                        $requestData['recipient'],
                        $requestData['locale'],
                        $requestData['parameter2']
                    );
                }
            }
            if ($emailTemplate === 'AlreadyRegisteredEmail' && str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->debug('Sending email address already registered email to user');

                    $this->emailClient->sendAlreadyRegisteredEmail(
                        $requestData['recipient'],
                        $requestData['locale']
                    );
                }
            }
            if ($emailTemplate === 'PasswordResetEmail' && str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->debug('Sending password reset request email to user');

                    $this->emailClient->sendPasswordResetEmail(
                        $requestData['recipient'],
                        $requestData['locale'],
                        $requestData['parameter2']
                    );
                }
            }
            if ($emailTemplate === 'PasswordChangedEmail' && str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->debug('Sending email to user to inform them that their password has changed');

                    $this->emailClient->sendPasswordChangedEmail(
                        $requestData['recipient'],
                        $requestData['locale']
                    );
                }
            }
            if ($emailTemplate === 'RequestChangeEmailToCurrentEmail' && str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->debug('Sending email to user informing them on how to complete their email reset');

                    $this->emailClient->sendRequestChangeEmailToCurrentEmail(
                        $requestData['recipient'],
                        $requestData['locale'],
                        $requestData['parameter2']
                    );
                }
            }
            if ($emailTemplate === 'RequestChangeEmailToNewEmail' && str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->notice('Sending user the new email address the user selected to reset their email to');

                    $this->emailClient->sendRequestChangeEmailToNewEmail(
                        $requestData['recipient'],
                        $requestData['locale'],
                        $requestData['parameter2']
                    );
                }
            }
            if ($emailTemplate === 'SomeoneTriedToUseYourEmailInEmailResetRequest' && str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->notice('Sending email to user to inform someone has tried to use their email on the service');

                    $this->emailClient->sendSomeoneTriedToUseYourEmailInEmailResetRequest(
                        $requestData['recipient'],
                        $requestData['locale']
                    );
                }
            }
            if ($emailTemplate === 'ActivationKeyRequestConfirmationEmail' && str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->notice('Sending an activation key request confirmation email to a user');

                    $this->emailClient->sendActivationKeyRequestConfirmationEmail(
                        $requestData['recipient'],
                        $requestData['referenceNumber'],
                        $requestData['postcode'],
                        $requestData['letterExpectedDate'],
                        $requestData['locale'],
                    );
                }
            }
            if ($emailTemplate === 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing' &&
                str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->notice('Sending an activation key request confirmation email to a user when LPA is identified not cleansed');

                    $this->emailClient->sendActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing(
                        $requestData['recipient'],
                        $requestData['referenceNumber'],
                        $requestData['letterExpectedDate'],
                        $requestData['locale'],
                    );
                }
            }
            if ($emailTemplate === 'NoAccountExistsEmail' && str_contains($property->getName(), $emailTemplate)) {
                if ($this->validate($requestData, $emailTemplate)) {
                    $this->logger->notice('Sending email to user to inform user did not exist');

                    $this->emailClient->sendNoAccountExistsEmail(
                        $requestData['recipient'],
                        $requestData['locale']
                    );
                }
            }

            $i++;
        }
    }

    private function validate(array $data, string $template): bool
    {
        if (!isset($data['recipient'])) {
            throw new BadRequestException('Recipient email address is missing to send an email');
        }
        if (!isset($data['locale'])) {
            throw new BadRequestException('Locale not set to send an email');
        }
        if (!isset($data['parameter2'])) {
            if ($template === 'AccountActivationEmail') {
                throw new BadRequestException('Activation link not set to send an email');
            } elseif ($template === 'AccountActivatedConfirmationEmail') {
                throw new BadRequestException('Login link not set to send an email');
            } elseif ($template === 'PasswordResetEmail') {
                throw new BadRequestException('Password reset token not set to send an email');
            } elseif ($template === 'RequestChangeEmailToCurrentEmail') {
                throw new BadRequestException('New email not set to send an email');
            } elseif ($template === 'RequestChangeEmailToNewEmail') {
                throw new BadRequestException('New email path not set to send an email');
            }
        }
        if (!isset($data['referenceNumber'])) {
            if ($template === 'ActivationKeyRequestConfirmationEmail' ||
                $template === 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing') {
                throw new BadRequestException('Reference number not set to send an email');
            }
        }
        if (!isset($data['postcode'])) {
            if ($template === 'ActivationKeyRequestConfirmationEmail') {
                throw new BadRequestException('Post code not set to send an email');
            }
        }
        if (!isset($data['letterExpectedDate'])) {
            if ($template === 'ActivationKeyRequestConfirmationEmail' ||
                $template === 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing') {
                throw new BadRequestException('Letter expected date not set to send an email');
            }
        }
        return true;
    }
}
