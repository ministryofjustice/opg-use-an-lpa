<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Exception\BadRequestException;
use App\Service\Log\EventCodes;
use Psr\Log\LoggerInterface;

/**
 * Single action invokable class that validates an LPA data structure as being able to be added to a users account
 */
class ValidateAccessForAllLpaRequirements
{
    public const NECESSARY_STATUS = 'Registered';

    /**
     * @param LoggerInterface $logger
     * @codeCoverageIgnore
     */
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param array $lpa An LPA data structure
     * @throws BadRequestException Thrown when unable to parse LPA registration date as a date
     */
    public function __invoke(string $lpaUid, string $status): void
    {
        $this->lpaHasNecessaryStatus($lpaUid, $status);
    }

    /**
     * @param string $lpaUid
     * @param string $status
     * @return void
     * @throws BadRequestException
     */
    public function lpaHasNecessaryStatus(string $lpaUid, string $status): void
    {
        if ($status !== self::NECESSARY_STATUS) {
            $this->logger->notice(
                'User entered LPA {uId} does not have the required status',
                [
                    'event_code' => EventCodes::OLDER_LPA_INVALID_STATUS,
                    'uId'        => $lpaUid,
                ]
            );
            throw new BadRequestException('LPA status invalid');
        }
    }
}
