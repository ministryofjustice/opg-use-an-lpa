<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use App\Service\Lpa\SiriusLpa;
use DateTimeInterface;

final class Lpa implements LpaInterface
{
    /**
     * @param array|SiriusLpa|null   $data       Array or object representing the LPA's data.
     * @param DateTimeInterface|null $lookupTime The datetime that the data was looked up in Sirius main database.
     */
    public function __construct(
        private array|SiriusLpa|null $data,
        private ?DateTimeInterface $lookupTime,
    ) {
    }

    public function getData(): array|SiriusLpa|null
    {
        return $this->data;
    }

    public function getLookupTime(): ?DateTimeInterface
    {
        return $this->lookupTime;
    }
}
