<?php

declare(strict_types=1);

namespace App\Service\Notify;

use App\Exception\BadRequestException;
use App\Service\Email\EmailClient;
use App\Service\Log\Output\Email;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Single action invokable class that validates parameters send to email client
 */
class NotifyService
{
    public function __construct(private LoggerInterface $logger, private EmailClient $emailClient)
    {
    }

    /**
     * @param array $requestData email parameters
     * @param EmailClient $emailClient
     * @param string $emailTemplate
     * @throws BadRequestException Thrown when parameters do not match
     */
    public function __invoke(string $emailTemplate, array $requestData): bool
    {
        //Instantiate the reflection object
        $reflector = new ReflectionClass($this->emailClient);
        $methods   = $reflector->getMethods();

        //Now go through the $properties array and populate each property
        foreach ($methods as $method) {
            if ($method->getName() === sprintf('send%s', $emailTemplate)) {
                if ($method->getNumberOfParameters() !== count($requestData)) {
                    throw new BadRequestException('Parameters count do not match expected');
                }

                $parameters = $method->getParameters();

                foreach ($parameters as $parameter) {
                    if (!array_key_exists($parameter->getName(), $requestData)) {
                        throw new BadRequestException(
                            'Parameter not set to send an email'
                        );
                    }
                }

                $this->logger->info(
                    'Sending {template} email to user with email {email}',
                    [
                        'template' => $emailTemplate,
                        'email'    => new Email($requestData['recipient']),
                    ],
                );

                $method->invokeArgs($this->emailClient, $requestData);

                $this->logger->debug('Email sent to user');
            }
        }
        return true;
    }
}
