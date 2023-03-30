<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Log\EventCodes;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use App\Service\Features\FeatureEnabled;

/**
 * Class ValidateOlderLpaRequirements
 *
 * Single action invokable class that validates an LPA data structure as being able to be added to a users account
 *
 * @package App\Service\Lpa
 */
class ValidateOlderLpaRequirements
{
    public const EARLIEST_REG_DATE = '2019-09-01';
    public const NECESSARY_STATUS = 'Registered';

    private DateTimeImmutable $earliestDate;
    private LoggerInterface $logger;
    private FeatureEnabled $featureEnabled;

    /**
     * ValidateOlderLpaRequirements constructor.
     *
     * @param LoggerInterface $logger
     * @codeCoverageIgnore
     */
    public function __construct(LoggerInterface $logger, FeatureEnabled $featureEnabled)
    {
        $this->logger = $logger;
        $this->earliestDate = new DateTimeImmutable(self::EARLIEST_REG_DATE);
        $this->featureEnabled = $featureEnabled;
    }

    /**
     * @param array $lpa An LPA data structure
     * @throws Exception Thrown when unable to parse LPA registration date as a date
     */
    public function __invoke(array $lpa): void
    {
        if ($lpa['status'] !== self::NECESSARY_STATUS) {
            $this->logger->notice(
                'User entered LPA {uId} does not have the required status',
                [
                    'event_code' => EventCodes::OLDER_LPA_INVALID_STATUS,
                    'uId' => $lpa['uId'],
                ]
            );
            throw new NotFoundException('LPA status invalid');
        }

        if (
            (!($this->featureEnabled)('allow_older_lpas')) &&
            (new DateTimeImmutable($lpa['registrationDate']) < $this->earliestDate)
        ) {
            $this->logger->notice(
                'User entered LPA {uId} has a registration date before 1 September 2019',
                [
                    'event_code' => EventCodes::OLDER_LPA_TOO_OLD,
                    'uId' => $lpa['uId'],
                ]
            );
            throw new BadRequestException('LPA not eligible due to registration date');
        }
    }
}
