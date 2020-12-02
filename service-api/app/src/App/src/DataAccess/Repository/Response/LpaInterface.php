<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use DateTime;

interface LpaInterface
{
    /**
     * Returns the data the makes up the LPA.
     */
    public function getData(): ?array;

    /**
     * Returns the Date & Time that the data was looked up in Sirius main database.
     *
     * In normal circumstance this will be within the last few minutes, but could be longer if Sirius is experiencing
     * a problem. It's our responsibility to check this and decide how to act accordingly.
     */
    public function getLookupTime(): ?DateTime;
}
