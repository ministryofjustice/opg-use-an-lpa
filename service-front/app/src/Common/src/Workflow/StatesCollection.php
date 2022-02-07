<?php

declare(strict_types=1);

namespace Common\Workflow;

use JsonSerializable;

class StatesCollection implements JsonSerializable
{
    /**
     * @var array<string, WorkflowState>
     */
    private array $states;

    public function __construct(array $states = [])
    {
        $this->states = $states;
    }

    /**
     * @param class-string $workflowStateClass
     *
     * @return bool
     */
    public function has(string $workflowStateClass): bool
    {
        return array_key_exists($workflowStateClass, $this->states);
    }

    /**
     * @param class-string $workflowStateClass
     *
     * @return WorkflowState
     * @throws StateNotInitialisedException
     */
    public function get(string $workflowStateClass): WorkflowState
    {
        if (! $this->has($workflowStateClass)) {
            throw new StateNotInitialisedException('State not available in state collection');
        }

        return $this->states[$workflowStateClass];
    }

    /**
     * @param string        $workflowStateClass
     * @param WorkflowState $state
     *
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
