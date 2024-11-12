<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTimeImmutable;
use EventSauce\ObjectHydrator\DoNotSerialize;

class Person
{
    public function __construct(
        public readonly ?string $line1,
        public readonly ?string $line2,
        public readonly ?string $line3,
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
    public function getLine1(): string|null
    {
        return $this->line1;
    }

    #[DoNotSerialize]
    public function getLine2(): string|null
    {
        return $this->line2;
    }

    #[DoNotSerialize]
    public function getLine3(): string|null
    {
        return $this->line3;
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
            ->setAddressLine1($this->getLine1())
            ->setAddressLine2($this->getLine2())
            ->setAddressLine3($this->getLine3())
            ->setTown($this->getTown())
            ->setCounty($this->getCounty())
            ->setPostcode($this->getPostCode())
            ->setCountry($this->getCountry())];
    }
}
