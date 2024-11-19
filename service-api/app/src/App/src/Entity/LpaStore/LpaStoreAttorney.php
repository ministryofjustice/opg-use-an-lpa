<?php

declare(strict_types=1);

namespace App\Entity\LpaStore;

use App\Entity\Casters\ExtractAddressLine1FromLpaStore;
use App\Entity\Casters\ExtractCountryFromLpaStore;
use App\Entity\Casters\ExtractTownFromLpaStore;
use App\Entity\Person;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;

class LpaStoreAttorney extends Person
{
    public function __construct(
        #[MapFrom('address')]
        #[ExtractAddressLine1FromLpaStore]
        ?string $addressLine1,
        ?string $addressLine2,
        ?string $addressLine3,
        #[MapFrom('address')]
        #[ExtractCountryFromLpaStore]
        ?string $country,
        ?string $county,
        #[MapFrom('dateOfBirth')]
        #[CastToDateTimeImmutable('!Y-m-d')]
        ?DateTimeImmutable $dob,
        ?string $email,
        #[MapFrom('firstNames')]
        ?string $firstnames,
        ?string $name,
        ?string $postcode,
        #[MapFrom('lastName')]
        ?string $surname,
        #[MapFrom('status')]
        ?string $systemStatus,
        #[MapFrom('address')]
        #[ExtractTownFromLpaStore]
        ?string $town,
        ?string $type,
        #[MapFrom('uid')]
        ?string $uId,
    ) {
        parent::__construct(
            $addressLine1,
            $addressLine2,
            $addressLine3,
            $country,
            $county,
            $dob,
            $email,
            $firstnames,
            $name,
            $postcode,
            $surname,
            $systemStatus,
            $town,
            $type,
            $uId,
        );
    }
}
