<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use DateTimeInterface;

final class ActorCodeExists
{
    public function __construct(
        public readonly ?DateTimeInterface $createdAt,
    ) {
    }
}
