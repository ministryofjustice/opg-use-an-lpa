<?php

declare(strict_types=1);

namespace Actor\Workflow;

use Common\Workflow\Traits\JsonSerializable;
use Common\Workflow\WorkflowState;
use DateTimeImmutable;

class RequestActivationKey implements WorkflowState
{
    use JsonSerializable;

    private ?ActorType $actorType                       = null;
    private ?ActorAddressResponse $actorAddressResponse = null;
    public ?DateTimeImmutable $dob;
    public ?DateTimeImmutable $donorDob;
    public ?DateTimeImmutable $attorneyDob;

    /**
     * Lovely constructor promotion
     */
    public function __construct(
        // these are kept across runs of the workflow
        public ?string $firstNames = null,
        public ?string $lastName = null,
        ?string $dob = null,
        public ?string $postcode = null,
        // these should be reset across runs
        public ?string $referenceNumber = null,
        ?string $actorType = null,
        public ?string $donorFirstNames = null,
        public ?string $donorLastName = null,
        ?string $donorDob = null,
        public ?string $actorAddress1 = null,
        public ?string $actorAddress2 = null,
        public ?string $actorAddressTown = null,
        public ?string $actorAddressCounty = null,
        public ?string $attorneyFirstNames = null,
        public ?string $attorneyLastName = null,
        ?string $attorneyDob = null,
        public ?string $addressOnPaper = null,
        public ?string $telephone = null,
        public ?bool $noTelephone = null,
        // not used for entered data but to track workflow path
        public ?string $actorUid = null,
        public ?bool $needsCleansing = null,
        ?string $actorAddressResponse = null,
        public ?string $liveInUK = null,
        public ?string $actorAbroadAddress = null,
    ) {
        if ($actorType !== null) {
            $this->setActorRole($actorType);
        }

        if ($actorAddressResponse !== null) {
            $this->setActorAddressResponse($actorAddressResponse);
        }

        // if only constructor promotion allowed data transformers
        $this->dob         = $dob !== null ? new DateTimeImmutable($dob) : null;
        $this->donorDob    = $donorDob !== null ? new DateTimeImmutable($donorDob) : null;
        $this->attorneyDob = $attorneyDob !== null ? new DateTimeImmutable($attorneyDob) : null;
    }

    /**
     * Reset the workflow to the start.
     *
     * identical information.
     */
    public function reset(): void
    {
        $this->referenceNumber    = null;
        $this->actorType          = null;
        $this->donorFirstNames    = null;
        $this->donorLastName      = null;
        $this->donorDob           = null;
        $this->attorneyFirstNames = null;
        $this->attorneyLastName   = null;
        $this->attorneyDob        = null;
        $this->actorAbroadAddress = null;
        $this->actorAddress1      = null;
        $this->actorAddress2      = null;
        $this->actorAddressTown   = null;
        $this->actorAddressCounty = null;
        $this->telephone          = null;
        $this->noTelephone        = null;
        $this->addressOnPaper     = null;

        $this->actorUid             = null;
        $this->needsCleansing       = false;
        $this->actorAddressResponse = null;

        $this->firstNames = null;
        $this->lastName   = null;
        $this->dob        = null;
        $this->liveInUK   = null;
        $this->postcode   = null;
    }

    public function getActorRole(): ?ActorType
    {
        return $this->actorType;
    }

    public function setActorRole(string $role): void
    {
        $this->actorType = ActorType::from($role);
    }

    public function getActorAddressResponse(): ?ActorAddressResponse
    {
        return $this->actorAddressResponse;
    }

    public function setActorAddressResponse(string $addressResponse): void
    {
        $this->actorAddressResponse = ActorAddressResponse::from($addressResponse);
    }
}
