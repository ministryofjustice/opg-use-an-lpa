<?php

namespace App\Service\Lpa;

use App\Exception\BadRequestException;
use App\Service\Log\EventCodes;
use Psr\Log\LoggerInterface;

class CheckLpaCleansed
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $actorDetailsMatch An LPA data structure
     * @throws Exception Thrown when LPA is not cleansed
     */
    public function __invoke(array $actorDetailsMatch): void
    {
        if ($actorDetailsMatch['lpaIsCleansed'] !== true) {
            $this->logger->notice(
                'User entered LPA {lpaId} is not cleansed',
                [
                    'event_code' => EventCodes::OLDER_LPA_NOT_CLEANSED,
                    'lpaId' => $actorDetailsMatch['lpa-id'],
                ]
            );
            throw new BadRequestException(
                'LPA is not cleansed',
                [
                    'actor' => $actorDetailsMatch
                ]
            );
        }
    }
}
