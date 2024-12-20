<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use App\Entity\Lpa as CombinedFormatLpa;
use App\Service\Lpa\SiriusLpa;
use DateTimeInterface;

/**
 * @template T
 * @implements LpaInterface<T>
 */
final class Lpa implements LpaInterface
{
    /**
     * @param array|SiriusLpa|CombinedFormatLpa|null $data Array or object representing the LPA's data.
     * @psalm-param T|null                           $data
     * @param DateTimeInterface|null                 $lookupTime The datetime that the data was looked up in
     *                                               Sirius main database.
     */
    public function __construct(
        private array|SiriusLpa|CombinedFormatLpa|null $data,
        private ?DateTimeInterface $lookupTime,
    ) {
    }

    public function getData(): array|SiriusLpa|CombinedFormatLpa|null
    {
        return $this->data;
    }

    public function getLookupTime(): ?DateTimeInterface
    {
        return $this->lookupTime;
    }
}
