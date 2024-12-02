<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTimeImmutable;
use EventSauce\ObjectHydrator\DoNotSerialize;

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

    public function getDob(): ?DateTimeImmutable
    {
        return $this->dob;
    }

    public function getLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function getLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function getLine3(): ?string
    {
        return $this->addressLine3;
    }

    public function getTown(): ?string
    {
        return $this->town;
    }

    public function getCounty(): ?string
    {
        return $this->county;
    }

    public function getPostCode(): ?string
    {
        return $this->postcode;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }


    public function getCompanyName(): ?string
    {
        return $this->name;
    }

    public function getAddresses(): array
    {
        return [
        (new Address())
            ->setAddressLine1($this->getLine1())
            ->setAddressLine2($this->getLine2())
            ->setAddressLine3($this->getLine3())
            ->setTown($this->getTown())
            ->setCounty($this->getCounty())
            ->setPostcode($this->getPostCode())
            ->setCountry($this->getCountry()),
        ];
    }

    public function getUid(): ?string
    {
        return $this->uId;
    }

    /**
     * @return string The id (or uid) of the person
     */
    public function getId(): ?string
    {
        return $this->uId;
    }
}
