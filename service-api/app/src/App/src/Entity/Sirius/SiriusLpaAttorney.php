<?php

declare(strict_types=1);

namespace App\Entity\Sirius;

use App\Enum\ActorStatus;
use App\Service\Lpa\AccessForAll\AddAccessForAllActorInterface;
use App\Service\Lpa\FindActorInLpa\ActorMatchingInterface;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;
use App\Entity\Sirius\Casters\{CastToSiriusActorStatus,
    ExtractAddressLine1FromSiriusLpa,
    ExtractAddressLine2FromSiriusLpa,
    ExtractAddressLine3FromSiriusLpa,
    ExtractCountryFromSiriusLpa,
    ExtractCountyFromSiriusLpa,
    ExtractPostcodeFromSiriusLpa,
    ExtractTownFromSiriusLpa,
    ExtractTypeFromSiriusLpa};
use App\Entity\Person;
use EventSauce\ObjectHydrator\MapFrom;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\PropertyCasters\CastToType;

class SiriusLpaAttorney extends Person implements ActorMatchingInterface, AddAccessForAllActorInterface
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
        #[CastToDateTimeImmutable('!Y-m-d')]
        ?DateTimeImmutable $dob,
        ?string $email,
        public readonly ?string $firstname,
        #[CastToType('string')]
        public readonly ?string $id,
        public readonly ?string $middlenames,
        ?string $otherNames,
        #[MapFrom('addresses')]
        #[ExtractPostcodeFromSiriusLpa]
        ?string $postcode,
        ?string $surname,
        #[CastToSiriusActorStatus]
        ?ActorStatus $systemStatus,
        #[MapFrom('addresses')]
        #[ExtractTownFromSiriusLpa]
        ?string $town,
        ?string $uId,
    ) {
        parent::__construct(
            addressLine1: $addressLine1,
            addressLine2: $addressLine2,
            addressLine3: $addressLine3,
            country:      $country,
            county:       $county,
            dob:          $dob,
            email:        $email,
            firstnames:   isset($firstname) ? trim(sprintf('%s %s', $firstname, $middlenames)) : null,
            name:         null,
            otherNames:   $otherNames,
            postcode:     $postcode,
            surname:      $surname,
            systemStatus: $systemStatus,
            town:         $town,
            uId:          $uId,
        );
    }

    public function getId(): string
    {
        return $this->id ?? '';
    }

    public function getFirstname(): string
    {
        return $this->firstname ?? '';
    }

    public function getMiddlenames(): string
    {
        return $this->middlenames ?? '';
    }
}
