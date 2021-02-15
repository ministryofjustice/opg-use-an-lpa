<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;

class GetAttorneyStatus
{
    public const ACTIVE_ATTORNEY = 0;
    public const GHOST_ATTORNEY = 1;
    public const INACTIVE_ATTORNEY = 2;

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(array $attorney): int
    {
        if (empty($attorney['firstname']) && empty($attorney['surname'])) {
            $this->logger->debug('Looked up attorney {id} but is a ghost', ['id' => $attorney['uId']]);
            return self::GHOST_ATTORNEY;
        }

        if (!$attorney['systemStatus']) {
            $this->logger->debug('Looked up attorney {id} but is inactive', ['id' => $attorney['uId']]);
            return self::INACTIVE_ATTORNEY;
        }

        return self::ACTIVE_ATTORNEY;
    }
}
