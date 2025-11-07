<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

final class ActorCodeIsValid
{
    public function __construct(
        public readonly ?string $actorUid,
        public readonly ?bool $hasPaperVerificationCode,
    ) {
    }
}
