<?php

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use App\Exception\NotFoundException;

class RestrictSendingLpaForCleansing
{
    public const EARLIEST_REG_DATE = '2019-09-01';

    private LoggerInterface $logger;
    private DateTimeImmutable $earliestDate;

    public function __construct(LoggerInterface $logger, LpaService $lpaService)
    {
        $this->logger = $logger;
        $this->earliestDate = new DateTimeImmutable(self::EARLIEST_REG_DATE);
    }

    /**
     * @param array $actorDetailsMatch
     * @param array $lpaData An LPA data structure
     * @throws Exception Thrown when LPA needs to restricted from cleansing
     */
    public function __invoke(array $lpaData, ?array $actorDetailsMatch): void
    {
        if (
            (new DateTimeImmutable($lpaData['registrationDate']) > $this->earliestDate) &&
            $actorDetailsMatch === null
        ) {
            $this->logger->info(
                'Restricting LPA {uId} from being sent for cleansing',
                [
                    'uId' => $lpaData['uId'],
                ]
            );
            throw new NotFoundException('LPA not found');
        }
    }
}
