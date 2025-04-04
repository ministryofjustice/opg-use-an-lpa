<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Exception\LpaNeedsCleansingException;
use App\Service\Lpa\FindActorInLpa\ActorMatch;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

class CheckLpaCleansed
{
    public const EARLIEST_REG_DATE = '2019-09-01';

    private DateTimeImmutable $earliestDate;

    public function __construct(private LpaManagerInterface $lpaManager, private LoggerInterface $logger)
    {
        $this->earliestDate = new DateTimeImmutable(self::EARLIEST_REG_DATE);
    }

    /**
     * @param ActorMatch $actorDetailsMatch An LPA data structure
     * @param string $userId
     * @throws Exception Thrown when LPA needs cleansed
     */
    public function __invoke(string $userId, ActorMatch $actorDetailsMatch): void
    {
        $lpa = $this->lpaManager->getByUid($actorDetailsMatch->lpaUId)->getData();

        if (
            !$lpa->getLpaIsCleansed() &&
            ($lpa->getRegistrationDate() < $this->earliestDate)
        ) {
            $this->logger->notice(
                'User {userId} requested an activation key for LPA {lpaId} which requires cleansing',
                [
                    'userId' => $userId,
                    'lpaId'  => $actorDetailsMatch->lpaUId,
                ]
            );

            // TODO fix actor_id !== actor_uid
            throw new LpaNeedsCleansingException(
                [
                    'actor_id' => $actorDetailsMatch->actor->getUid(),
                ]
            );
        }
    }
}
