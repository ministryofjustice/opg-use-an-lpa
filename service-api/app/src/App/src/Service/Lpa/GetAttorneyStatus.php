<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Enum\ActorStatus;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use Psr\Log\LoggerInterface;
use App\Service\Lpa\GetAttorneyStatus\AttorneyStatus;

class GetAttorneyStatus
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(GetAttorneyStatusInterface $attorney): AttorneyStatus
    {
        if (empty($attorney->getFirstnames()) && empty($attorney->getSurname())) {
            $this->logger->debug('Looked up attorney {id} but is a ghost', ['id' => $attorney->getUid()]);
            return AttorneyStatus::GHOST_ATTORNEY;
        }

        $systemStatus = $attorney->getStatus();
        if (!$systemStatus || $systemStatus === ActorStatus::INACTIVE) {
            $this->logger->debug('Looked up attorney {id} but is inactive', ['id' => $attorney->getUid()]);
            return AttorneyStatus::INACTIVE_ATTORNEY;
        }

        if ($systemStatus === ActorStatus::REPLACEMENT) {
            $this->logger->debug('Looked up attorney {id} but is a replacement', ['id' => $attorney->getUid()]);
            return AttorneyStatus::REPLACEMENT_ATTORNEY;
        }

        return AttorneyStatus::ACTIVE_ATTORNEY;
    }
}
