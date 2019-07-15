<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTime;

class User
{
    /** @var string */
    protected $id;

    /** @var DateTime */
    protected $lastSignedIn;

    /**
     * User constructor.
     * @param string $firstname
     * @param string $surname
     * @param DateTime $lastSignedIn
     */
    public function __construct(string $id, DateTime $lastSignedIn)
    {
        $this->id = $id;
        $this->lastSignedIn = $lastSignedIn;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getLastSignedIn(): DateTime
    {
        return $this->lastSignedIn;
    }

}