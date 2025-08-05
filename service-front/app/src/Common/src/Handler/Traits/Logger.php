<?php

declare(strict_types=1);

namespace Common\Handler\Traits;

use Psr\Log\LoggerInterface;

/**
 * @psalm-require-implements \Common\Handler\LoggerAware
 */
trait Logger
{
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
