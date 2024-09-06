<?php

declare(strict_types=1);

namespace App\Entity\Sirius;

use App\Entity\Person;
use App\Entity\Sirius\Casters\{
    ExtractAddressLine1FromSiriusLpa,
    ExtractAddressLine2FromSiriusLpa,
    ExtractAddressLine3FromSiriusLpa,
    ExtractCountryFromSiriusLpa,
    ExtractCountyFromSiriusLpa,
    ExtractPostcodeFromSiriusLpa,
    ExtractTownFromSiriusLpa,
    ExtractTypeFromSiriusLpa,
};
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastToType;

class SiriusLpaDonor extends Person
{
    public function __construct(
        ?string $uId,
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
        #[MapFrom('addresses')]
        #[ExtractTypeFromSiriusLpa]
        ?string $type,
        ?DateTimeImmutable $dob,
        ?string $email,
        #[MapFrom('firstname')]
        ?string $firstname,
        #[MapFrom('firstNames')]
        ?string $firstnames,
        ?string $surname,
        ?string $otherNames,
        #[CastToType('string')]
        ?string $systemStatus,
    ) {
        parent::__construct(
            $uId,
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
