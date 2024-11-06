<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTimeImmutable;

class Person
{
    public function __construct(
        public readonly ?string $addressLine1,
        public readonly ?string $addressLine2,
        public readonly ?string $addressLine3,
        public readonly ?string $country,
        public readonly ?string $county,
        public readonly ?DateTimeImmutable $dob,
        public readonly ?string $email,
        public readonly ?string $firstname,
        public readonly ?string $firstnames,
        public readonly ?string $name,
        public readonly ?string $otherNames,
        public readonly ?string $postcode,
        public readonly ?string $surname,
        public readonly ?string $systemStatus,
        public readonly ?string $town,
        public readonly ?string $type,
        public readonly ?string $uId,
    ) {
    }

    public function getSalutation(): ?string
    {
        return '';
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getMiddlenames(): ?string
    {
        return $this->otherNames;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function getDob(): DateTimeImmutable|null
    {
        return $this->dob;
    }

    public function getCompanyName(): ?string
    {
        return $this->name;
    }

}
