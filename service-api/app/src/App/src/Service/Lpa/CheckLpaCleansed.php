<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Exception\BadRequestException;
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
     * @param array $actorDetailsMatch An LPA data structure
     * @param string $userId
     * @throws Exception Thrown when LPA needs cleansed
     */
    public function __invoke(string $userId, ActorMatch $actorDetailsMatch): void
    {
        $lpa = $this->lpaManager->getByUid($actorDetailsMatch->lpaUId)->getData();

        if (
            !$lpa['lpaIsCleansed'] &&
            (new DateTimeImmutable($lpa['registrationDate']) < $this->earliestDate)
        ) {
            $this->logger->notice(
                'User {userId} requested an activation key for LPA {lpaId} which requires cleansing',
                [
                    'userId' => $userId,
                    'lpaId'  => $actorDetailsMatch->lpaUId,
                ]
            );

            // TODO fix actor_id !== actor_uid
            throw new BadRequestException(
                'LPA needs cleansing',
                [
                    'actor_id' => $actorDetailsMatch->actor->getUid(),
                ]
            );
        }
    }
}
