<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Casters\ExtractAddressLine1FromDataStore;
use App\Entity\Casters\ExtractCountryFromDataStore;
use App\Entity\Casters\ExtractTownFromDataStore;
use DateTimeInterface;
use EventSauce\ObjectHydrator\MapFrom;

class TrustCorporations extends Person
{
    public function __construct(
        #[MapFrom('name')]
        public readonly ?string $companyName,
        ?string $name,
        #[MapFrom('address')]
        #[ExtractAddressLine1FromDataStore]
        ?string $addressLine1,
        ?string $addressLine2,
        ?string $addressLine3,
        #[MapFrom('address')]
        #[ExtractCountryFromDataStore]
        ?string $country,
        ?string $county,
        ?string $postcode,
        #[MapFrom('address')]
        #[ExtractTownFromDataStore]
        ?string $town,
        ?string $type,
        ?DateTimeInterface $dob,
        ?string $email,
        ?string $firstname,
        ?string $firstnames,
        ?string $surName,
        ?string $otherNames,
        #[MapFrom('status')]
        ?string $systemStatus,
    ) {
        parent::__construct(
            $name,
            $addressLine1,
            $addressLine2,
            $addressLine3,
            $country,
            $county,
            $postcode,
            $town,
            $type,
            $dob,
            $email,
            $firstname,
            $firstnames,
            $surName,
            $otherNames,
            $systemStatus
        );
    }
}