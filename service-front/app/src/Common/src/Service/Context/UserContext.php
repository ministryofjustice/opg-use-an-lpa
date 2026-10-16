<?php

declare(strict_types=1);

namespace Common\Service\Context;

final class UserContext
{
    public function __construct(
        public ?string $userId = null,
        public ?string $sessionId = null,
    ) {
    }
}
