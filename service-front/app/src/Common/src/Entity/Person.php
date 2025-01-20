<?php

declare(strict_types=1);

namespace Common\Entity;

use Common\Service\Lpa\ServiceInterfaces\ProcessPersonsInterface;
use DateTimeImmutable;
use DateTimeInterface;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;

class Person implements ProcessPersonsInterface
{
    public function __construct(
        public readonly ?string $addressLine1,
        public readonly ?string $addressLine2,
        public readonly ?string $addressLine3,
        public readonly ?string $country,
        public readonly ?string $county,
        #[CastToDateTimeImmutable('!Y-m-d')]
        public readonly ?DateTimeImmutable $dob,
        public readonly ?string $email,
        public readonly ?string $firstnames,
        public readonly ?string $name,
        public readonly ?string $otherNames,
        public readonly ?string $postcode,
        public readonly ?string $surname,
        public readonly ?string $systemStatus,
        public readonly ?string $town,
        public readonly ?string $uId,
    ) {
    }

    public function getDob(): DateTimeInterface
    {
        return $this->dob ?? new DateTimeImmutable('1900-01-01');
    }

    public function getFirstname(): string
    {
        return $this->firstnames ?? '';
    }

    public function getMiddlenames(): string
    {
        return '';
    }

    public function getSurname(): string
    {
        return $this->surname ?? '';
    }

    public function getSalutation(): ?string
    {
        return '';
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function getAddressLine3(): ?string
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

    public function getPostcode(): ?string
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

    public function getUid(): ?string
    {
        return $this->uId;
    }

    /**
     * @return ?string The id (or uid) of the person
     */
    public function getId(): ?string
    {
        return $this->uId;
    }

    public function getSystemStatus(): bool
    {
        return (bool) $this->systemStatus;
    }
}
