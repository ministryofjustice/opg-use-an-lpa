<?php

namespace App\Service\Lpa;

use App\Exception\BadRequestException;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;

class CheckLpaCleansed
{
    public const EARLIEST_REG_DATE = '2019-09-01';

    private LoggerInterface $logger;
    private LpaService $lpaService;
    private DateTimeImmutable $earliestDate;

    public function __construct(LoggerInterface $logger, LpaService $lpaService)
    {
        $this->logger = $logger;
        $this->lpaService = $lpaService;
        $this->earliestDate = new DateTimeImmutable(self::EARLIEST_REG_DATE);
    }

    /**
     * @param array $actorDetailsMatch An LPA data structure
     * @param string $userId
     * @throws Exception Thrown when LPA needs cleansed
     */
    public function __invoke(string $userId, array $actorDetailsMatch): void
    {
        $lpa = $this->lpaService->getByUid((string) $actorDetailsMatch['lpa-id'])->getData();

        if (
            !$lpa['lpaIsCleansed'] &&
            (new DateTimeImmutable($lpa['registrationDate']) < $this->earliestDate)
        ) {
            $this->logger->notice(
                'User {userId} requested an activation key for LPA {lpaId} which requires cleansing',
                [
                    'userId' => $userId,
                    'lpaId' => $actorDetailsMatch['lpa-id'],
                ]
            );

            throw new BadRequestException(
                'LPA needs cleansing',
                [
                    'actor_id'      => $actorDetailsMatch['actor']['uId']
                ]
            );
        }
    }
}
