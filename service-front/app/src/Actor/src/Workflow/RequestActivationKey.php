<?php

declare(strict_types=1);

namespace Actor\Workflow;

use Actor\Workflow\Traits\JsonSerializable;
use Common\Workflow\WorkflowState;
use DateTimeImmutable;
use RuntimeException;

class RequestActivationKey implements WorkflowState
{
    use JsonSerializable;

    // TODO replace with enums at PHP 8.1
    public const ACTOR_TYPE_DONOR    = 'donor';
    public const ACTOR_TYPE_ATTORNEY = 'attorney';

    public const ACTOR_ADDRESS_SELECTION_YES      = 'Yes';
    public const ACTOR_ADDRESS_SELECTION_NO       = 'No';
    public const ACTOR_ADDRESS_SELECTION_NOT_SURE = 'Not sure';

    private ?string $actorType = null;
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
        public ?int $referenceNumber = null,
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
        public ?int $actorUid = null,
        public ?bool $needsCleansing = null,
        public ?string $actorAddressResponse = null,
    ) {
        if ($actorType !== null) { // TODO replace with enums at PHP 8.1
            $this->setActorRole($actorType);
        }

        if ($actorAddressResponse !== null) { // TODO replace with enums at PHP 8.1
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
     * @codeCoverageIgnore
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
        $this->lastName = null;
        $this->dob = null;
        $this->postcode = null;
    }

    /**
     * TODO replace with enums at PHP 8.1
     *
     * @return string|null
     */
    public function getActorRole(): ?string
    {
        return $this->actorType;
    }

    /**
     * TODO replace with enums at PHP 8.1
     *
     * @param string $role
     * @psalm-param self::ACTOR_TYPE_* $role
     * @throws RuntimeException
     */
    public function setActorRole(string $role): void
    {
        if (!in_array($role, [self::ACTOR_TYPE_ATTORNEY, self::ACTOR_TYPE_DONOR])) {
            throw new RuntimeException(sprintf("Actor type '%s' not recognised", $role));
        }

        $this->actorType = $role;
    }

    /**
     * TODO replace with enums at PHP 8.1
     *
     * @return string|null
     */
    public function getActorAddressCheckResponse(): ?string
    {
        return $this->actorAddressResponse;
    }

    /**
     * TODO replace with enums at PHP 8.1
     *
     * @param string $addressResponse
     * @psalm-param self::ACTOR_ADDRESS_SELECTION_* $addressResponse
     * @throws RuntimeException
     */
    public function setActorAddressResponse(string $addressResponse): void
    {
        if (
            !in_array(
                $addressResponse,
                [
                    self::ACTOR_ADDRESS_SELECTION_YES,
                    self::ACTOR_ADDRESS_SELECTION_NO,
                    self::ACTOR_ADDRESS_SELECTION_NOT_SURE,
                ]
            )
        ) {
            throw new RuntimeException(sprintf("Actor address response '%s' not recognised", $addressResponse));
        }

        $this->actorAddressResponse = $addressResponse;
    }
}
