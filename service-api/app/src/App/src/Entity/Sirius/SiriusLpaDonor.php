<?php

declare(strict_types=1);

namespace App\Entity\Sirius;

use App\Entity\Person;
use App\Service\Lpa\FindActorInLpa\ActorMatchingInterface;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;
use App\Entity\Sirius\Casters\{
    ExtractAddressLine1FromSiriusLpa,
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

class SiriusLpaDonor extends Person implements ActorMatchingInterface
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
        #[MapFrom('linked')]
        #[LinkedDonorCaster]
        public readonly ?array $linked,
        public readonly ?string $middlenames,
        public readonly ?string $otherNames,
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
            isset($firstname) ? trim(sprintf('%s %s', $firstname, $middlenames)) : null,
            null,
            $postcode,
            $surname,
            $systemStatus,
            $town,
            $type,
            $uId,
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
