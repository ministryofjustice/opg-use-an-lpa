<?php

declare(strict_types=1);

namespace App\Entity\LpaStore;

use App\Entity\Casters\ExtractAddressLine1FromLpaStore;
use App\Entity\Casters\ExtractCountryFromLpaStore;
use App\Entity\Casters\ExtractTownFromLpaStore;
use App\Entity\Person;
use App\Service\Lpa\GetTrustCorporationStatus\TrustCorporationStatusInterface;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\DoNotSerialize;
use EventSauce\ObjectHydrator\MapFrom;

class LpaStoreTrustCorporations extends Person implements TrustCorporationStatusInterface
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

    #[DoNotSerialize]
    public function getCompanyName(): string
    {
        return $this->companyName();
    }

    #[DoNotSerialize]
    public function getSystemStatus(): bool|string
    {
        return $this->systemStatus;
    }

    #[DoNotSerialize]
    public function getUid(): string
    {
        return $this->uId;
    }
}
