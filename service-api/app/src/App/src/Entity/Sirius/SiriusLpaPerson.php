<?php

declare(strict_types=1);

namespace App\Entity\Sirius;

use App\Entity\Sirius\Casters\ExtractAddressLine1FromSiriusLpa;
use App\Entity\Sirius\Casters\ExtractAddressLine2FromSiriusLpa;
use App\Entity\Sirius\Casters\ExtractAddressLine3FromSiriusLpa;
use App\Entity\Sirius\Casters\ExtractCountryFromSiriusLpa;
use App\Entity\Sirius\Casters\ExtractCountyFromSiriusLpa;
use App\Entity\Sirius\Casters\ExtractPostcodeFromSiriusLpa;
use App\Entity\Sirius\Casters\ExtractTownFromSiriusLpa;
use App\Entity\Person;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;

class SiriusLpaPerson extends Person
{
    public function __construct(
        ?string $name,
        #[MapFrom('addresses')]
        #[ExtractAddressLine1FromSiriusLpa]
        ?string $addressLine1,
        #[MapFrom('addresses')]
        #[ExtractAddressLine2FromSiriusLpa]
        ?string $addressLine2,
        #[MapFrom('addresses')]
        #[ExtractAddressLine3FromSiriusLpa]
        ?string $addressLine3,
        #[MapFrom('addresses')]
        #[ExtractCountryFromSiriusLpa]
        ?string $country,
        #[MapFrom('addresses')]
        #[ExtractCountyFromSiriusLpa]
        ?string $county,
        #[MapFrom('addresses')]
        #[ExtractPostcodeFromSiriusLpa]
        ?string $postcode,
        #[MapFrom('addresses')]
        #[ExtractTownFromSiriusLpa]
        ?string $town,
        ?string $type,
        #[CastToDateTimeImmutable('!d-m-Y')]
        ?DateTimeImmutable $dob,
        ?string $email,
        #[MapFrom('firstname')]
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
            $systemStatus,
        );
    }
}
