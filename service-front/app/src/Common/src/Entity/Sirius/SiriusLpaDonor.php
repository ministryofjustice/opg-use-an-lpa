<?php

declare(strict_types=1);

namespace Common\Entity\Sirius;

use Common\Entity\Person;
use Common\Entity\Casters\{ExtractAddressLine1FromSiriusLpa,
    ExtractAddressLine2FromSiriusLpa,
    ExtractAddressLine3FromSiriusLpa,
    ExtractCountryFromSiriusLpa,
    ExtractCountyFromSiriusLpa,
    ExtractPostcodeFromSiriusLpa,
    ExtractTownFromSiriusLpa,
    ExtractTypeFromSiriusLpa,
    LinkedDonorCaster};
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastToType;

class SiriusLpaDonor extends Person
{
    public function __construct(
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
        ?DateTimeImmutable $dob,
        ?string $email,
        #[MapFrom('firstname')]
        ?string $firstname,
        #[MapFrom('firstNames')]
        ?string $firstnames,
        #[MapFrom('linked')]
        #[LinkedDonorCaster]
        public readonly ?array $linked,
        ?string $name,
        ?string $otherNames,
        #[MapFrom('addresses')]
        #[ExtractPostcodeFromSiriusLpa]
        ?string $postcode,
        ?string $surname,
        #[CastToType('string')]
        ?string $systemStatus,
        #[MapFrom('addresses')]
        #[ExtractTownFromSiriusLpa]
        ?string $town,
        #[MapFrom('addresses')]
        #[ExtractTypeFromSiriusLpa]
        ?string $type,
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
            $firstname,
            $firstnames,
            $name,
            $otherNames,
            $postcode,
            $surname,
            $systemStatus,
            $town,
            $type,
            $uId,
        );
    }
}