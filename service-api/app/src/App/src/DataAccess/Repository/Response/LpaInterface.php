<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use App\Entity\Lpa as CombinedFormatLpa;
use App\Service\Lpa\SiriusLpa;
use DateTimeInterface;

/**
 * @template T
 */
interface LpaInterface
{
    /**
     * Returns the data the makes up the LPA.
     *
     * @psalm-return T|null
     */
    public function getData(): array|SiriusLpa|CombinedFormatLpa|null;

    /**
     * Returns the Date & Time that the data was looked up in Sirius main database.
     *
     * In normal circumstance this will be within the last few minutes, but could be longer if Sirius is experiencing
     * a problem. It's our responsibility to check this and decide how to act accordingly.
     */
    public function getLookupTime(): ?DateTimeInterface;
}
