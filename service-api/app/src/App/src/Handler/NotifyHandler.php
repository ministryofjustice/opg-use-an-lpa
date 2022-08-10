<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Email\EmailClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Laminas\Diactoros\Response\JsonResponse;

/**
 * Class NotifyHandler
 *
 * @package App\Handler
 * @codeCoverageIgnore
 */
class NotifyHandler implements RequestHandlerInterface
{
    private LoggerInterface $logger;
    private EmailClient $emailClient;

    /**
     * NotifyHandler constructor.
     *
     * @param EmailClient $emailClient
     * @param LoggerInterface $logger
     */
    public function __construct(EmailClient $emailClient, LoggerInterface $logger)
    {
        $this->emailClient = $emailClient;
        $this->logger = $logger;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $emailTemplate = $request->getAttribute('emailTemplate');

        if ($emailTemplate === 'AccountActivationEmail') {
            if (!isset($requestData['recipient']) || !isset($requestData['locale']) || !isset($requestData['parameter2'])) {
                throw new BadRequestException('Email address, locale and template must be provided');
            }

            $this->logger->debug('Sending account activation email to user');

            $this->emailClient->sendAccountActivationEmail(
                $requestData['recipient'],
                $requestData['locale'],
                $requestData['parameter2']
            );
        }

        if ($emailTemplate === 'AccountActivatedConfirmationEmail') {
            if (!isset($requestData['recipient']) || !isset($requestData['locale']) || !isset($requestData['parameter2'])) {
                throw new BadRequestException('Email address, locale and template must be provided');
            }

            $this->logger->debug('Sending account activation confirmation email to user');

            $this->emailClient->sendAccountActivatedConfirmationEmail(
                $requestData['recipient'],
                $requestData['locale'],
                $requestData['parameter2']
            );
        }

        if ($emailTemplate === 'AlreadyRegisteredEmail') {
            if (!isset($requestData['recipient']) || !isset($requestData['locale'])) {
                throw new BadRequestException('Email address and locale must be provided');
            }

            $this->logger->debug('Sending email address already registered email to user');
            $this->emailClient->sendAlreadyRegisteredEmail(
                $requestData['recipient'],
                $requestData['locale']
            );
        }

        if ($emailTemplate === 'PasswordResetEmail') {
            if (!isset($requestData['recipient']) || !isset($requestData['locale']) || !isset($requestData['parameter2'])) {
                throw new BadRequestException('Email address, locale and template must be provided');
            }

            $this->logger->debug('Sending password reset request email to user');

            $this->emailClient->sendPasswordResetEmail(
                $requestData['recipient'],
                $requestData['locale'],
                $requestData['parameter2']
            );
        }

        if ($emailTemplate === 'PasswordChangedEmail') {
            if (!isset($requestData['recipient']) || !isset($requestData['locale'])) {
                throw new BadRequestException('Email address and locale must be provided');
            }

            $this->logger->debug('Sending email to user to inform them that their password has changed');
            $this->emailClient->sendPasswordChangedEmail(
                $requestData['recipient'],
                $requestData['locale']
            );
        }

        if ($emailTemplate === 'RequestChangeEmailToCurrentEmail') {
            if (!isset($requestData['recipient']) || !isset($requestData['locale']) || !isset($requestData['parameter2'])) {
                throw new BadRequestException('Email address, locale and template must be provided');
            }

            $this->logger->debug('Sending email to user informing them on how to complete their email reset');

            $this->emailClient->sendRequestChangeEmailToCurrentEmail(
                $requestData['recipient'],
                $requestData['locale'],
                $requestData['parameter2']
            );
        }

        if ($emailTemplate === 'RequestChangeEmailToNewEmail') {
            if (!isset($requestData['recipient']) || !isset($requestData['locale']) || !isset($requestData['parameter2'])) {
                throw new BadRequestException('Email address, locale and template must be provided');
            }

            $this->logger->debug('Sending user the new email address the user selected to reset their email to');

            $this->emailClient->sendRequestChangeEmailToCurrentEmail(
                $requestData['recipient'],
                $requestData['locale'],
                $requestData['parameter2']
            );
        }

        if ($emailTemplate === 'SomeoneTriedToUseYourEmailInEmailResetRequest') {
            if (!isset($requestData['recipient']) || !isset($requestData['locale'])) {
                throw new BadRequestException('Email address and locale must be provided');
            }

            $this->logger->debug('Sending email to user to inform someone has tried to use their email on the service');
            $this->emailClient->sendSomeoneTriedToUseYourEmailInEmailResetRequest(
                $requestData['recipient'],
                $requestData['locale']
            );
        }

        if ($emailTemplate === 'ActivationKeyRequestConfirmationEmail') {
            if (!isset($requestData['recipient']) ||
                !isset($requestData['locale']) ||
                !isset($requestData['referenceNumber']) ||
                !isset($requestData['postcode']) ||
                !isset($requestData['letterExpectedDate'])
            ) {
                throw new BadRequestException(
                    'The following must be provided: email address, locale, reference number, postcode, letterExpectedDate'
                );
            }

            $this->logger->debug('Sending an activation key request confirmation email to a user');
            $this->emailClient->sendActivationKeyRequestConfirmationEmail(
                $requestData['recipient'],
                $requestData['referenceNumber'],
                $requestData['postcode'],
                $requestData['letterExpectedDate'],
                $requestData['locale'],
            );
        }

        if ($emailTemplate === 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing') {
            if (!isset($requestData['recipient']) ||
                !isset($requestData['locale']) ||
                !isset($requestData['referenceNumber']) ||
                !isset($requestData['letterExpectedDate'])
            ) {
                throw new BadRequestException(
                    'The following must be provided: email address, locale, reference number, letterExpectedDate'
                );
            }

            $this->logger->debug(
                'Sending an activation key request confirmation email to a user when LPA is identified not cleansed'
            );
            $this->emailClient->sendActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing(
                $requestData['recipient'],
                $requestData['referenceNumber'],
                $requestData['letterExpectedDate'],
                $requestData['locale'],
            );
        }

        if ($emailTemplate === 'NoAccountExistsEmail') {
            if (!isset($requestData['recipient']) || !isset($requestData['locale'])) {
                throw new BadRequestException('Email address and locale must be provided');
            }

            $this->logger->debug('Sending email to user to inform user did not exist');
            $this->emailClient->sendNoAccountExistsEmail(
                $requestData['recipient'],
                $requestData['locale']
            );
        }

        return new JsonResponse([]);
    }
}