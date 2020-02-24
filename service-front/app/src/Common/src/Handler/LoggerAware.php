<?php

declare(strict_types=1);

namespace Common\Handler;

use Psr\Log\LoggerInterface;

interface LoggerAware
{
    /**
     * Must attempt to return a configured PSR3 logging interface
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface;
}