<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use DateTime;

interface ActorCodeInterface
{
    /**
     * Returns the data the makes up the LPA.
     *
     * @return array|null
     */
    public function getData(): ?array;

    /**
     * Returns the Date & Time that the data was looked up in the Codes service.
     *
     * @return DateTime|null
     */
    public function getLookupTime(): ?DateTime;
}
