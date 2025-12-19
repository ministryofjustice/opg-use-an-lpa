<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Log\EventCodes;
use App\Service\Lpa\FindActorInLpa\ActorMatch;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use App\Exception\NotFoundException;
use App\Service\Lpa\RestrictSendingLpaForCleansing\RestrictSendingLpaForCleansingInterface;

class RestrictSendingLpaForCleansing
{
    public const string EARLIEST_REG_DATE = '2019-09-01';

    private DateTimeImmutable $earliestDate;

    public function __construct(private LoggerInterface $logger)
    {
        $this->earliestDate = new DateTimeImmutable(self::EARLIEST_REG_DATE);
    }

    /**
     * @param ?ActorMatch $actorDetailsMatch
     * @param RestrictSendingLpaForCleansingInterface $lpaData An LPA data structure
     * @throws NotFoundException Thrown when LPA needs to restricted from cleansing
     */
    public function __invoke(RestrictSendingLpaForCleansingInterface $lpaData, ?ActorMatch $actorDetailsMatch): void
    {
        $lpaPassesAgeRequirements = $lpaData->getRegistrationDate() >= $this->earliestDate;
        if (
            ($lpaPassesAgeRequirements || $lpaData->getLpaIsCleansed()) &&
            $actorDetailsMatch === null
        ) {
            $this->logger->notice(
                'Restricting LPA {uId} from being sent for cleansing',
                [
                    'event_code' => $lpaPassesAgeRequirements ?
                        EventCodes::OLDER_LPA_PARTIAL_MATCH_TOO_RECENT :
                        EventCodes::OLDER_LPA_PARTIAL_MATCH_HAS_BEEN_CLEANSED,
                    'uId'        => $lpaData->getUid(),
                ]
            );
            throw new NotFoundException('LPA not found');
        }
    }
}
