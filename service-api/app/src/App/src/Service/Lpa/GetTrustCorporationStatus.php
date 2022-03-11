<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;

class GetTrustCorporationStatus
{
    public const ACTIVE_TC = 0;
    public const GHOST_TC = 1;
    public const INACTIVE_TC = 2;

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(array $trustCorporation): int
    {
        if (empty($trustCorporation['companyName'])) {
            $this->logger->debug(
                'Looked up attorney {id} but company name not found',
                ['id' => $trustCorporation['uId']]
            );
            return self::GHOST_TC;
        }

        if (!$trustCorporation['systemStatus']) {
            $this->logger->debug('Looked up attorney {id} but is inactive', ['id' => $trustCorporation['uId']]);
            return self::INACTIVE_TC;
        }

        return self::ACTIVE_TC;
    }
}