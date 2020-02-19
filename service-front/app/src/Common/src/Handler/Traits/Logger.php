<?php

declare(strict_types=1);

namespace Common\Handler\Traits;

use Psr\Log\LoggerInterface;

trait Logger
{
    public function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            throw new \RuntimeException('Logger interface property not initialised before attempt to fetch');
        }

        return $this->logger;
    }
}