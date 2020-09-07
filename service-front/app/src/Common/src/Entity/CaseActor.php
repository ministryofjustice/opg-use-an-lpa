<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTime;

class CaseActor
{
    /** @var int */
    protected $id;

    /** @var string|null */
    protected $uId = null;

    /** @var array<array<mixed>>|null */
    protected $linked = null;

    /** @var string|null */
    protected $email = null;

    /** @var DateTime|null */
    protected $dob = null;

    /** @var string|null */
    protected $salutation = null;

    /** @var string|null */
    protected $firstname = null;

    /** @var string|null */
    protected $middlenames = null;

    /** @var string|null */
    protected $surname = null;

    /** @var string|null */
    protected $companyName = null;

    /** @var bool */
    protected $systemStatus = null;

    /** @var Address[] */
    protected $addresses = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUId(): ?string
    {
        return $this->uId;
    }

    public function setUId(string $uId): void
    {
        $this->uId = $uId;
    }

    /**
     * @return ?array<int>
     */
    public function getIds(): ?array
    {
        if ($this->linked === null) {
            return [$this->getId()];
        }

        return array_map(function($x) {
            return $x['id'];
        }, $this->linked);
    }

    /**
     * @param array<array<mixed>> $linked
     */
    public function setLinked(array $linked): void
    {
        $this->linked = $linked;;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getDob(): ?DateTime
    {
        return $this->dob;
    }

    public function setDob(DateTime $dob): void
    {
        $this->dob = $dob;
    }

    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    public function setSalutation(string $salutation): void
    {
        $this->salutation = $salutation;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getMiddlenames(): ?string
    {
        return $this->middlenames;
    }

    public function setMiddlenames(string $middlenames): void
    {
        $this->middlenames = $middlenames;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): void
    {
        $this->surname = $surname;
    }

    public function getAddresses(): array
    {
        return $this->addresses;
    }

    public function setAddresses(array $addresses): void
    {
        $this->addresses = $addresses;
    }

    public function getSystemStatus(): bool
    {
        return $this->systemStatus;
    }

    public function setSystemStatus(bool $systemStatus): void
    {
        $this->systemStatus = $systemStatus;
    }
}
