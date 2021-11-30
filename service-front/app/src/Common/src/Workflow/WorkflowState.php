<?php

declare(strict_types=1);

namespace Common\Workflow;

interface WorkflowState
{
    /**
     * Populates a state object with an array of key value pairs like one might find in a session.
     *
     * @param array<string,mixed> $values
     */
    public function build(array $values): void;

    /**
     * Reset the workflow to the beginning.
     *
     * This does not clear the name, date of birth or postcode as it is likely a repeat journey would use
     * identical information.
     */
    public function reset(): void;
}
