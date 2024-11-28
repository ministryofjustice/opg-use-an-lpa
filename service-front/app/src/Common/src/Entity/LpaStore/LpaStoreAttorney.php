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

class LpaStoreAttorney extends Person
{
    public function __construct(
        #[MapFrom('address')]
        #[ExtractAddressLine1FromLpaStore]
        ?string $line1,
        ?string $line2,
        ?string $line3,
        #[MapFrom('address')]
        #[ExtractCountryFromLpaStore]
        ?string $country,
        ?string $county,
        #[MapFrom('dateOfBirth')]
        ?DateTimeImmutable $dob,
        ?string $email,
        #[MapFrom('firstname')]
        ?string $firstname,
        #[MapFrom('firstNames')]
        ?string $firstnames,
        ?string $name,
        ?string $otherNames,
        #[MapFrom('address')]
        #[ExtractPostcodeFromLpaStore]
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
            $line1,
            $line2,
            $line3,
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
