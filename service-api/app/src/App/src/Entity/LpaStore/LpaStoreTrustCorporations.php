<?php

declare(strict_types=1);

namespace App\Entity\LpaStore;

use App\Entity\Casters\ExtractAddressLine1FromLpaStore;
use App\Entity\Casters\ExtractCountryFromLpaStore;
use App\Entity\Casters\ExtractTownFromLpaStore;
use App\Entity\Person;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;

class LpaStoreTrustCorporations extends Person
{
    public function __construct(
        #[MapFrom('name')]
        public readonly ?string $companyName,
        #[MapFrom('uid')]
        ?string $uId,
        ?string $name,
        #[MapFrom('address')]
        #[ExtractAddressLine1FromLpaStore]
        ?string $addressLine1,
        ?string $addressLine2,
        ?string $addressLine3,
        #[MapFrom('address')]
        #[ExtractCountryFromLpaStore]
        ?string $country,
        ?string $county,
        ?string $postcode,
        #[MapFrom('address')]
        #[ExtractTownFromLpaStore]
        ?string $town,
        ?string $type,
        #[MapFrom('dateOfBirth')]
        ?DateTimeImmutable $dob,
        ?string $email,
        ?string $firstname,
        ?string $firstnames,
        ?string $surname,
        ?string $otherNames,
        #[MapFrom('status')]
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
