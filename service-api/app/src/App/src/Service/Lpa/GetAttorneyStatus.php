<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;
use App\Service\Lpa\GetAttorneyStatus\AttorneyStatus;

class GetAttorneyStatus
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(array $attorney): AttorneyStatus
    {
        if (empty($attorney['firstname']) && empty($attorney['surname'])) {
            $this->logger->debug('Looked up attorney {id} but is a ghost', ['id' => $attorney['uId']]);
            return AttorneyStatus::GHOST_ATTORNEY;
        }

        if (!$attorney['systemStatus']) {
            $this->logger->debug('Looked up attorney {id} but is inactive', ['id' => $attorney['uId']]);
            return AttorneyStatus::INACTIVE_ATTORNEY;
        }

        return AttorneyStatus::ACTIVE_ATTORNEY;
    }
}
