<?php

declare(strict_types=1);

namespace App\Entity\Sirius;

use EventSauce\ObjectHydrator\DoNotSerialize;
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
use App\Entity\Person;
use EventSauce\ObjectHydrator\MapFrom;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\PropertyCasters\CastToType;
use JsonSerializable;

class SiriusLpaAttorney extends Person implements JsonSerializable
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

    #[DoNotSerialize]
    public function jsonSerialize(): mixed
    {
        $data = get_object_vars($this);

        array_walk($data, function (&$value) {
            if ($value instanceof DateTimeImmutable) {
                $value = $value->format('Y-m-d H:i:s.uO');
            }
        });

        return $data;
    }
}
