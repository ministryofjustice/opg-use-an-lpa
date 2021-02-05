<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;

class GetAttorneyStatus
{
    private const ACTIVE_ATTORNEY = 0;
    private const GHOST_ATTORNEY = 1;
    private const INACTIVE_ATTORNEY = 2;

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(array $attorney): int
    {
        if (empty($attorney['firstname']) && empty($attorney['surname'])) {
            $this->logger->info('Looked up attorney {id} but is a ghost', ['id' => $attorney['id']]);
            return self::GHOST_ATTORNEY;
        }

        if (!$attorney['systemStatus']) {
            $this->logger->info('Looked up attorney {id} but is inactive', ['id' => $attorney['id']]);
            return self::INACTIVE_ATTORNEY;
        }

        return self::ACTIVE_ATTORNEY;
    }
}
