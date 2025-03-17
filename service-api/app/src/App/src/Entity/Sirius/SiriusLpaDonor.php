<?php

declare(strict_types=1);

namespace App\Entity\Sirius;

use App\Entity\Casters\ExtractAddressFieldFrom;
use App\Entity\Person;
use App\Enum\ActorStatus;
use App\Service\Lpa\AccessForAll\AddAccessForAllActorInterface;
use App\Service\Lpa\FindActorInLpa\ActorMatchingInterface;
use App\Service\Lpa\LpaAlreadyAdded\DonorInformationInterface;
use EventSauce\ObjectHydrator\PropertyCasters\CastToDateTimeImmutable;
use App\Entity\Sirius\Casters\{CastToSiriusActorStatus, CastToUnhyphenatedUId, LinkedDonorCaster};
use DateTimeImmutable;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastToType;

class SiriusLpaDonor extends Person implements
    ActorMatchingInterface,
    AddAccessForAllActorInterface,
    DonorInformationInterface
{
    public function __construct(
        #[MapFrom('addresses')]
        #[ExtractAddressFieldFrom('addressLine1')]
        ?string $addressLine1,
        #[MapFrom('addresses')]
        #[ExtractAddressFieldFrom('addressLine2')]
        ?string $addressLine2,
        #[MapFrom('addresses')]
        #[ExtractAddressFieldFrom('addressLine3')]
        ?string $addressLine3,
        #[MapFrom('addresses')]
        #[ExtractAddressFieldFrom('country')]
        ?string $country,
        #[MapFrom('addresses')]
        #[ExtractAddressFieldFrom('county')]
        ?string $county,
        #[CastToDateTimeImmutable('!Y-m-d')]
        ?DateTimeImmutable $dob,
        ?string $email,
        public readonly ?string $firstname,
        #[CastToType('string')]
        public readonly ?string $id,
        #[LinkedDonorCaster]
        public readonly ?array $linked,
        public readonly ?string $middlenames,
        ?string $otherNames,
        #[MapFrom('addresses')]
        #[ExtractAddressFieldFrom('postcode')]
        ?string $postcode,
        ?string $surname,
        #[CastToSiriusActorStatus]
        ?ActorStatus $systemStatus,
        #[MapFrom('addresses')]
        #[ExtractAddressFieldFrom('town')]
        ?string $town,
        #[CastToUnhyphenatedUId]
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

    public function getMiddleNames(): string
    {
        return $this->middlenames ?? '';
    }

    public function getFirstnames(): string
    {
        return $this->firstname ?? '';
    }

    public function getSurname(): string
    {
        return $this->surname ?? '';
    }

    public function getUid(): string
    {
        return $this->uId ?? '';
    }
}
