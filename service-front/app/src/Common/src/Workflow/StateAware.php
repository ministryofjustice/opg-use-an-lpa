<?php

declare(strict_types=1);

namespace Common\Workflow;

interface StateAware
{
    /**
     * @return callable(array<string,mixed>): WorkflowState
     */
    public function stateFactory(): callable;

    public function state(): WorkflowState;
}
