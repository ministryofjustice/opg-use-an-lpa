<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use DateTime;

final class ActorCode implements ActorCodeInterface
{

    /**
     * Array representing the LPA's data.
     *
     * @var array
     */
    private $data;

    /**
     * The datetime that the data was looked up in Sirius main database.
     *
     * @var DateTime
     */
    private $lookupTime;

    public function __construct(?array $data, ?DateTime $lookupTime)
    {
        $this->data = $data;
        $this->lookupTime = $lookupTime;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getLookupTime(): ?DateTime
    {
        return $this->lookupTime;
    }
}
