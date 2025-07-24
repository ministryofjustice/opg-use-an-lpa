<?php

declare(strict_types=1);

namespace App\Service\Lpa\Combined;

use App\DataAccess\Repository\Response\LpaInterface;
use App\Exception\GoneException;
use App\Exception\MissingCodeExpiryException;
use App\Exception\NotFoundException;
use DateTimeInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

class RejectInvalidLpa
{
    public function __construct(
        readonly private ClockInterface $clock,
        readonly private LoggerInterface $logger,
    ) {
    }

    /**
     * @param LpaInterface $lpa
     * @param string       $viewerCode
     * @param string       $donorSurname
     * @param array        $validationData
     * @return void
     * @throws GoneException
     * @throws MissingCodeExpiryException
     * @throws NotFoundException
     */
    public function __invoke(LpaInterface $lpa, string $viewerCode, string $donorSurname, array $validationData): void
    {
        if (!isset($validationData['Expires']) || !($validationData['Expires'] instanceof DateTimeInterface)) {
            $this->logger->info(
                'The code {code} entered by user to view LPA does not have an expiry date set.',
                ['code' => $viewerCode]
            );
            throw new MissingCodeExpiryException();
        }

        // Does the donor match? If not then return nothing (Lpa not found with those details)
        if (
            strtolower($lpa->getData()->getDonor()->getSurname()) !== strtolower($donorSurname)
        ) {
            $this->logger->info(
                'The donor entered by the user to view the lpa with {code} does not match',
                ['code' => $viewerCode]
            );
            throw new NotFoundException();
        }

        if ($this->clock->now() > $validationData['Expires']) {
            $this->logger->info('The code {code} entered by user to view LPA has expired.', ['code' => $viewerCode]);
            throw new GoneException('Share code expired');
        }

        if (isset($validationData['Cancelled'])) {
            $this->logger->info('The code {code} entered by user is cancelled.', ['code' => $viewerCode]);
            throw new GoneException('Share code cancelled');
        }
    }
}
