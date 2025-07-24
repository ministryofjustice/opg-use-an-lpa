<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * @template T
 * @implements ResponseInterface<T>
 */
final class UpstreamResponse implements ResponseInterface
{
    /**
     * @psalm-param T           $data
     */
    public function __construct(
        private readonly mixed $data,
        private readonly DateTimeInterface $lookupTime = new DateTimeImmutable(),
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getLookupTime(): DateTimeInterface
    {
        return $this->lookupTime;
    }
}
