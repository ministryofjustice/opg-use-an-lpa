<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\GetTrustCorporationStatus\TrustCorporationStatus;
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
            return TrustCorporationStatus::GHOST_TC->value;
        }

        $systemStatus = $trustCorporation->getStatus();
        if (!$systemStatus || $systemStatus === 'false') {
            $this->logger->debug('Looked up attorney {id} but is inactive', ['id' => $trustCorporation->getUid()]);
            return TrustCorporationStatus::INACTIVE_TC->value;
        }

        return TrustCorporationStatus::ACTIVE_TC->value;
    }
}
