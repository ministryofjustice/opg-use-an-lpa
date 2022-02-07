<?php

declare(strict_types=1);

namespace Actor\Workflow;

use Common\Workflow\WorkflowState;
use DateTimeImmutable;

class RequestActivationKey implements WorkflowState
{
    // TODO replace with enums at PHP 8.1
    public const ACTOR_DONOR = 'donor';
    public const ACTOR_ATTORNEY = 'attorney';

    public ?DateTimeImmutable $dob;
    public ?DateTimeImmutable $donorDob;

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
        private ?string $actorType = null,
        public ?string $donorFirstNames = null,
        public ?string $donorLastName = null,
        ?string $donorDob = null,
        public ?string $telephone = null,
        public ?bool $noTelephone = null,
        // not used for entered data but to track workflow path
        public ?int $actorUid = null,
        public bool $needsCleaning = false,
    ) {
        // if only constructor promotion allowed data transformers
        $this->dob = $dob !== null ? new DateTimeImmutable($dob) : null;
        $this->donorDob = $donorDob !== null ? new DateTimeImmutable($donorDob) : null;
    }

    public function has(string $property): bool
    {
        return $this->$property !== null;
    }

    /**
     * Reset the workflow to the start.
     *
     * This does not clear the name, date of birth or postcode as it is likely a repeat journey would use
     * identical information.
     */
    public function reset(): void
    {
        $this->referenceNumber = null;
        $this->actorType = null;
        $this->donorFirstNames = null;
        $this->donorLastName = null;
        $this->donorDob = null;
        $this->telephone = null;
        $this->noTelephone = null;

        $this->actorUid = null;
        $this->needsCleaning = false;
    }

    public function getActorRole(): ?string
    {
        return $this->actorType;
    }

    /**
     * @param string $role
     * @psalm-param self::ACTOR_* $role
     */
    public function setActorRole(string $role): void
    {
        if (!in_array($role, [self::ACTOR_ATTORNEY, self::ACTOR_DONOR])) {
            throw new \RuntimeException("Actor type '$role' not recognised");
        }

        $this->actorType = $role;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $serialized = [];

        foreach (get_object_vars($this) as $prop => $value) {
            if ($value !== null) {
                if ($value instanceof DateTimeImmutable) {
                    $serialized[$prop] = $value->format('c');
                } else {
                    $serialized[$prop] = $value;
                }
            }
        }

        return $serialized;
    }
}
