<?php

declare(strict_types=1);

namespace Common\Workflow;

use JsonSerializable;

final class StatesCollection implements JsonSerializable
{
    /**
     * @param array<class-string, WorkflowState> $states
     */
    public function __construct(private array $states = [])
    {
    }

    /**
     * @param class-string $workflowStateClass
     * @return bool
     */
    public function has(string $workflowStateClass): bool
    {
        return array_key_exists($workflowStateClass, $this->states);
    }

    /**
     * @template T of WorkflowState
     * @param class-string<T> $workflowStateClass
     * @return T
     * @throws StateNotInitialisedException
     * @throws UnexpectedValueException
     */
    public function get(string $workflowStateClass): WorkflowState
    {
        if (! $this->has($workflowStateClass)) {
            throw new StateNotInitialisedException('State not available in state collection');
        }

        $state = $this->states[$workflowStateClass];

        if (! $state instanceof $workflowStateClass) {
            throw new StateNotInitialisedException('Stored workflow state does not match the requested class');
        }

        return $state;
    }

    /**
     * @param class-string  $workflowStateClass
     * @param WorkflowState $state
     * @return void
     */
    public function add(string $workflowStateClass, WorkflowState $state): void
    {
        $this->states[$workflowStateClass] = $state;
    }

    /**
     * @return array<class-string, WorkflowState>
     */
    public function jsonSerialize(): array
    {
        return $this->states;
    }
}
