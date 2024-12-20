<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\GetTrustCorporationStatus\TrustCorporationStatuses;
use App\Service\Lpa\GetTrustCorporationStatus\GetTrustCorporationStatusInterface;
use Psr\Log\LoggerInterface;

class GetTrustCorporationStatus
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(GetTrustCorporationStatusInterface $trustCorporation): int
    {

        if (empty($trustCorporation->getCompanyName())) {
            $this->logger->debug(
                'Looked up attorney {id} but company name not found',
                ['id' => $trustCorporation->getUid()]
            );
            return TrustCorporationStatuses::GHOST_TC->value;
        }

        $systemStatus = $trustCorporation->getStatus();
        if (!$systemStatus || $systemStatus === 'false') {
            $this->logger->debug('Looked up attorney {id} but is inactive', ['id' => $trustCorporation->getUid()]);
            return TrustCorporationStatuses::INACTIVE_TC->value;
        }

        return TrustCorporationStatuses::ACTIVE_TC->value;
    }
}
