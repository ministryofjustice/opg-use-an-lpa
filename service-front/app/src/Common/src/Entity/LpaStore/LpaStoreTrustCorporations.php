<?php

declare(strict_types=1);

namespace Common\Entity\LpaStore;

use Common\Entity\Casters\ExtractAddressLine1FromLpaStore;
use Common\Entity\Casters\ExtractCountryFromLpaStore;
use Common\Entity\Casters\ExtractPostcodeFromLpaStore;
use Common\Entity\Casters\ExtractTownFromLpaStore;
use Common\Entity\Person;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;

class LpaStoreTrustCorporations extends Person
{
    public function __construct(
        #[MapFrom('address')]
        #[ExtractAddressLine1FromLpaStore]
        ?string $addressLine1,
        ?string $addressLine2,
        ?string $addressLine3,
        #[MapFrom('name')]
        public readonly ?string $companyName,
        #[MapFrom('address')]
        #[ExtractCountryFromLpaStore]
        ?string $country,
        ?string $county,
        #[MapFrom('dateOfBirth')]
        ?DateTimeImmutable $dob,
        ?string $email,
        ?string $firstname,
        ?string $firstnames,
        ?string $name,
        ?string $otherNames,
        #[MapFrom('address')]
        #[ExtractPostcodeFromLpaStore]
        ?string $postcode,
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

    public function companyName(): ?string
    {
        return $this->companyName;
    }
}
