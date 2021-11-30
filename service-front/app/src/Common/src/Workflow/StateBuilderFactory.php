<?php

declare(strict_types=1);

namespace Common\Workflow;

use ReflectionClass;
use ReflectionException;
use RuntimeException;

class StateBuilderFactory
{
    /**
     * @param class-string<WorkflowState> $class
     *
     * @return callable(array<string,mixed>): WorkflowState
     */
    public function __invoke(string $class): callable
    {
        try {
            $reflect = new ReflectionClass($class);

            if (! $reflect->implementsInterface(WorkflowState::class)) {
                throw new RuntimeException(
                    'Requested WorkflowState class does not implement \Common\Workflow\WorkflowStat'
                );
            }
        } catch (ReflectionException $rex) {
            throw new RuntimeException(
                'Requested WorkflowState class is not a valid class name',
                500,
                $rex
            );
        }

        return function (array $stateData) use ($class): WorkflowState {
            $state = new $class();
            $state->build($stateData);

            return $state;
        };
    }
}
