<?php

declare(strict_types=1);

namespace App\Service\Lpa\GetTrustCorporationStatus;

use Psr\Log\LoggerInterface;

class GetTrustCorporationStatus
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(array $trustCorporation): int
    {
        if (empty($trustCorporation['companyName'])) {
            $this->logger->debug(
                'Looked up attorney {id} but company name not found',
                ['id' => $trustCorporation['uId']]
            );
            return TrustCorporationStatuses::GHOST_TC->value;
        }

        if (!$trustCorporation['systemStatus']) {
            $this->logger->debug('Looked up attorney {id} but is inactive', ['id' => $trustCorporation['uId']]);
            return TrustCorporationStatuses::INACTIVE_TC->value;
        }

        return TrustCorporationStatuses::ACTIVE_TC->value;
    }
}
