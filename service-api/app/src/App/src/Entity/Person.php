<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Casters\ExtractAddressLine1FromDataStore;
use App\Entity\Casters\ExtractCountryFromDataStore;
use App\Entity\Casters\ExtractTownFromDataStore;
use DateTimeInterface;
use EventSauce\ObjectHydrator\MapFrom;

class Person
{
    public function __construct(
        public readonly ?string $name,
        #[MapFrom('address')]
        #[ExtractAddressLine1FromDataStore]
        public readonly ?string $addressLine1,
        public readonly ?string $addressLine2,
        public readonly ?string $addressLine3,
        #[MapFrom('address')]
        #[ExtractCountryFromDataStore]
        public readonly ?string $country,
        public readonly ?string $county,
        public readonly ?string $postcode,
        #[MapFrom('address')]
        #[ExtractTownFromDataStore]
        public readonly ?string $town,
        public readonly ?string $type,
        #[MapFrom('dateOfBirth')]
        public readonly ?DateTimeInterface $dob,
        public readonly ?string $email,
        #[MapFrom('firstname')]
        public readonly ?string $firstname,
        #[MapFrom('firstNames')]
        public readonly ?string $firstnames,
        #[MapFrom('lastName')]
        public readonly ?string $surName,
        public readonly ?string $otherNames,
        #[MapFrom('status')]
        public readonly ?string $systemStatus,
    ) {
    }
}