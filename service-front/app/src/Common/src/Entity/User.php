<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTime;

class User
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $firstname;

    /** @var string */
    protected $surname;

    /** @var DateTime */
    protected $lastSignedIn;

    /**
     * User constructor.
     * @param string $firstname
     * @param string $surname
     * @param DateTime $lastSignedIn
     */
    public function __construct(string $id, string $firstname, string $surname, DateTime $lastSignedIn)
    {
        $this->id = $id;
        $this->firstname = $firstname;
        $this->surname = $surname;
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
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getSurname(): string
    {
        return $this->surname;
    }

    /**
     * @return DateTime
     */
    public function getLastSignedIn(): DateTime
    {
        return $this->lastSignedIn;
    }

}