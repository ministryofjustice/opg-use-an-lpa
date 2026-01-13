<?php

declare(strict_types=1);

namespace App\Entity\LpaStore;

use App\Entity\Casters\ExtractAddressFieldFrom;
use App\Entity\Casters\ExtractAddressLine1FromLpaStore;
use App\Entity\Casters\ExtractCountryFromLpaStore;
use App\Entity\Casters\ExtractTownFromLpaStore;
use App\Entity\Person;
use App\Enum\ActorStatus;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;

class LpaStoreTrustCorporation extends Person
{
    public function __construct(
        #[MapFrom('address')]
        #[ExtractAddressFieldFrom('line1')]
        ?string $line1,
        #[MapFrom('address')]
        #[ExtractAddressFieldFrom('line2')]
        ?string $line2,
        #[MapFrom('address')]
        #[ExtractAddressFieldFrom('line3')]
        ?string $line3,
        #[MapFrom('address')]
        #[ExtractAddressFieldFrom('country')]
        ?string $country,
        ?string $county,
        #[CastToDateTimeImmutable('!Y-m-d')]
        ?DateTimeImmutable $dateOfBirth,
        ?string $email,
        ?string $firstNames,
        ?string $name, // only found on trust corporations
        #[MapFrom('address')]
        #[ExtractAddressFieldFrom('postcode')]
        ?string $postcode,
        ?string $lastName,
        ?ActorStatus $status,
        #[MapFrom('address')]
        #[ExtractAddressFieldFrom('town')]
        ?string $town,
        #[MapFrom('uid')]
        ?string $uId,
        #[MapFrom('cannotMakeJointDecisions')]
        ?bool $cannotMakeJointDecisions,
    ) {
        parent::__construct(
            addressLine1:               $line1,
            addressLine2:               $line2,
            addressLine3:               $line3,
            country:                    $country,
            county:                     $county,
            dob:                        $dateOfBirth,
            email:                      $email,
            firstnames:                 $firstNames,
            name:                       $name,
            otherNames:                 null,
            postcode:                   $postcode,
            surname:                    $lastName,
            systemStatus:               $status,
            town:                       $town,
            uId:                        $uId,
            cannotMakeJointDecisions:   $cannotMakeJointDecisions
        );
    }
}
