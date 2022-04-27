<?php

declare(strict_types=1);

namespace Actor\Workflow;

use Common\Workflow\WorkflowState;
use DateTimeImmutable;
use Exception;

class AddLpa implements WorkflowState
{
    public ?DateTimeImmutable $dateOfBirth;

    /**
     * Lovely constructor promotion
     *
     * @throws Exception
     */
    public function __construct(
        public ?string $activationKey = null,
        ?string $dateOfBirth = null,
        public ?string $lpaReferenceNumber = null,
    ) {
        $this->dateOfBirth = $dateOfBirth !== null ? new DateTimeImmutable($dateOfBirth) : null;
    }

    /**
     * Reset the workflow to the start.
     */
    public function reset(): void
    {
        $this->activationKey = null;
        $this->dateOfBirth = null;
        $this->lpaReferenceNumber = null;
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
