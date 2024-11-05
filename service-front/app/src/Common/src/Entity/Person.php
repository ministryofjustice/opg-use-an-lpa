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

    #[DoNotSerialize]
    public function getSalutation(): ?string
    {
        return '';
    }

    #[DoNotSerialize]
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    #[DoNotSerialize]
    public function getMiddlenames(): ?string
    {
        return $this->otherNames;
    }

    #[DoNotSerialize]
    public function getSurname(): ?string
    {
        return $this->surname;
    }

    #[DoNotSerialize]
    public function getDob(): DateTimeImmutable|null
    {
        return $this->dob;
    }

    #[DoNotSerialize]
    public function getAddressLine1(): string|null
    {
        return $this->addressLine1;
    }

    #[DoNotSerialize]
    public function getAddressLine2(): string|null
    {
        return $this->addressLine2;
    }

    #[DoNotSerialize]
    public function getAddressLine3(): string|null
    {
        return $this->addressLine3;
    }

    #[DoNotSerialize]
    public function getTown(): string|null
    {
        return $this->town;
    }

    #[DoNotSerialize]
    public function getCounty(): string|null
    {
        return $this->county;
    }

    #[DoNotSerialize]
    public function getPostCode(): string|null
    {
        return $this->postcode;
    }

    #[DoNotSerialize]
    public function getCountry(): string|null
    {
        return $this->country;
    }

    #[DoNotSerialize]
    public function getCompanyName(): string|null
    {
        return $this->name;
    }

    #[DoNotSerialize]
    public function getAddresses(): array
    {
        return [(new Address())
            ->setAddressLine1($this->getAddressLine1())
            ->setAddressLine2($this->getAddressLine2())
            ->setAddressLine3($this->getAddressLine3())
            ->setTown($this->getTown())
            ->setCounty($this->getCounty())
            ->setPostcode($this->getPostCode())
            ->setCountry($this->getCountry())];
    }
}
