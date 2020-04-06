<?php

declare(strict_types=1);

namespace Common\Service\Security;

interface RateLimiterInterface
{
    public function isLimited(string $identity): bool;

    public function limit(string $identity): void;

    public function getName(): string;
}