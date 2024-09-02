<?php

declare(strict_types=1);

namespace App\Entity\DataStore;

use App\Entity\Casters\ExtractAddressLine1FromDataStore;
use App\Entity\Casters\ExtractCountryFromDataStore;
use App\Entity\Casters\ExtractTownFromDataStore;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;

class DataStoreDonor extends DataStorePerson
{
    public function __construct(
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
        #[MapFrom('dateOfBirth')]
        ?DateTimeImmutable $dob,
        ?string $email,
        ?string $firstname,
        #[MapFrom('firstNames')]
        ?string $firstnames,
        #[MapFrom('lastName')]
        ?string $surname,
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
            $surname,
            $otherNames,
            $systemStatus
        );
    }
}
