<?php

declare(strict_types=1);

namespace Common\Service\Email;

use Alphagov\Notifications\Client as NotifyClient;

/**
 * Class EmailClient
 * @package Common\Service\Email
 */
class EmailClient
{
    /**
     * Template IDs for the notify client
     */
    const TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED = '4af9acf0-f2c1-4ecc-8441-0e2173890463';

    /**
     * @var NotifyClient
     */
    private $notifyClient;

    /**
     * EmailClient constructor.
     * @param NotifyClient $notifyClient
     */
    public function __construct(NotifyClient $notifyClient)
    {
        $this->notifyClient = $notifyClient;
    }

    /**
     * @param string $recipient
     */
    public function sendAlreadyRegisteredEmail(string $recipient)
    {
        $this->notifyClient->sendEmail($recipient, self::TEMPLATE_ID_EMAIL_ADDRESS_ALREADY_REGISTERED);
    }
}
