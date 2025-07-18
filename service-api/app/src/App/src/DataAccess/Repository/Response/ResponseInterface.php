<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use DateTimeInterface;

/**
 * @template T
 */
interface ResponseInterface
{
    /**
     * Returns a DTO of the upstream API response
     *
     * @psalm-return T
     */
    public function getData(): mixed;

    /**
     * Returns the Date and time that the data was retrieved
     *
     * @return DateTimeInterface
     */
    public function getLookupTime(): DateTimeInterface;
}
