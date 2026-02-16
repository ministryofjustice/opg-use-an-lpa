<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ActorStatus;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\GetTrustCorporationStatus\GetTrustCorporationStatusInterface;
use App\Service\Lpa\LpaAlreadyAdded\DonorInformationInterface;
use App\Service\Lpa\ResolveActor\ResolveActorInterface;
use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;

class Person implements
    JsonSerializable,
    GetAttorneyStatusInterface,
    DonorInformationInterface,
    GetTrustCorporationStatusInterface,
    ResolveActorInterface
{
    public function __construct(
        public readonly ?string $addressLine1,
        public readonly ?string $addressLine2,
        public readonly ?string $addressLine3,
        public readonly ?bool $cannotMakeJointDecisions,
        public readonly ?string $country,
        public readonly ?string $county,
        public readonly ?DateTimeImmutable $dob,
        public readonly ?string $email,
        public readonly ?string $firstnames,
        public readonly ?string $name,
        public readonly ?string $otherNames,
        public readonly ?string $postcode,
        public readonly ?string $surname,
        public readonly ?ActorStatus $systemStatus,
        public readonly ?string $town,
        public readonly ?string $uId,
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = get_class_vars(Person::class);

        array_walk($data, function (&$value, $key) {
            if ($key === 'dob' && $this->dob !== null) {
                $value = $this->dob->format('Y-m-d');
            } elseif ($key !== 'dob') {
                $value = $this->$key;
            }
        });

        return $data;
    }

    public function getUid(): string
    {
        return $this->uId ?? '';
    }

    public function getFirstnames(): string
    {
        return $this->firstnames ?? '';
    }

    public function getSurname(): string
    {
        return $this->surname ?? '';
    }

    public function getStatus(): ActorStatus
    {
        return $this->systemStatus ?? ActorStatus::INACTIVE;
    }

    public function getCompanyName(): ?string
    {
        return $this->name ?? '';
    }

    public function getPostCode(): string
    {
        return $this->postcode ?? '';
    }

    public function getDob(): DateTimeInterface
    {
        return $this->dob ?? new DateTimeImmutable('1800-01-01');
    }

    /**
     * @return string The id (or uid) of the person
     */
    public function getId(): string
    {
        return $this->uId ?? '';
    }

    public function getMiddleNames(): string
    {
        return '';
    }
}
