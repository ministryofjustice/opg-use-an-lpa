<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Log\EventCodes;
use Exception;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use App\Exception\NotFoundException;

class RestrictSendingLpaForCleansing
{
    public const EARLIEST_REG_DATE = '2019-09-01';

    private DateTimeImmutable $earliestDate;

    public function __construct(private LoggerInterface $logger)
    {
        $this->earliestDate = new DateTimeImmutable(self::EARLIEST_REG_DATE);
    }

    /**
     * @param ?array $actorDetailsMatch
     * @param array $lpaData An LPA data structure
     * @throws Exception Thrown when LPA needs to restricted from cleansing
     */
    public function __invoke(array $lpaData, ?array $actorDetailsMatch): void
    {
        $lpaPassesAgeRequirements = new DateTimeImmutable($lpaData['registrationDate']) >= $this->earliestDate;
        if (
            ($lpaPassesAgeRequirements || $lpaData['lpaIsCleansed']) &&
            $actorDetailsMatch === null
        ) {
            $this->logger->notice(
                'Restricting LPA {uId} from being sent for cleansing',
                [
                    'event_code' => $lpaPassesAgeRequirements ?
                        EventCodes::OLDER_LPA_PARTIAL_MATCH_TOO_RECENT :
                        EventCodes::OLDER_LPA_PARTIAL_MATCH_HAS_BEEN_CLEANSED,
                    'uId'        => $lpaData['uId'],
                ]
            );
            throw new NotFoundException('LPA not found');
        }
    }
}
