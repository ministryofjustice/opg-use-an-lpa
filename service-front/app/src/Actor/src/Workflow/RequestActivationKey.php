<?php

declare(strict_types=1);

namespace Actor\Workflow;

use Common\Workflow\WorkflowState;
use DateTimeImmutable;
use RuntimeException;

class RequestActivationKey implements WorkflowState
{
    // TODO replace with enums at PHP 8.1
    public const ACTOR_DONOR = 'donor';
    public const ACTOR_ATTORNEY = 'attorney';

    private ?string $actorType = null;
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
        ?string $actorType = null,
        public ?string $donorFirstNames = null,
        public ?string $donorLastName = null,
        ?string $donorDob = null,
        public ?string $telephone = null,
        public ?bool $noTelephone = null,
        // not used for entered data but to track workflow path
        public ?int $actorUid = null,
        public ?bool $needsCleansing = null,
    ) {
        if ($actorType !== null) { // TODO replace with enums at PHP 8.1
            $this->setActorRole($actorType);
        }

        // if only constructor promotion allowed data transformers
        $this->dob = $dob !== null ? new DateTimeImmutable($dob) : null;
        $this->donorDob = $donorDob !== null ? new DateTimeImmutable($donorDob) : null;
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
        $this->needsCleansing = false;
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
     * @psalm-param self::ACTOR_* $role
     *
     * @throws RuntimeException
     */
    public function setActorRole(string $role): void
    {
        if (!in_array($role, [self::ACTOR_ATTORNEY, self::ACTOR_DONOR])) {
            throw new RuntimeException("Actor type '$role' not recognised");
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
