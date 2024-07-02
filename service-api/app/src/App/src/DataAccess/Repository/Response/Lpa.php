<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use DateTimeInterface;

final class Lpa implements LpaInterface
{
    /**
     * Array representing the LPA's data.
     *
     * @var array
     */
    private array $data;

    /**
     * The datetime that the data was looked up in Sirius main database.
     */
    private DateTimeInterface $lookupTime;

    public function __construct(?array $data, ?DateTimeInterface $lookupTime)
    {
        $this->data       = $data;
        $this->lookupTime = $lookupTime;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getLookupTime(): ?DateTimeInterface
    {
        return $this->lookupTime;
    }
}
