<?php

declare(strict_types=1);

namespace App\Entity;

use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\DoNotSerialize;

class Person implements GetAttorneyStatusInterface
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
    public function getUid(): string
    {
        return $this->uId;
    }

    #[DoNotSerialize]
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    #[DoNotSerialize]
    public function getSurname(): string
    {
        return $this->surname;
    }

    #[DoNotSerialize]
    public function getSystemStatus(): bool|string
    {
        return $this->systemStatus;
    }
}
