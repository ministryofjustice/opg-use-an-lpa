<?php

declare(strict_types=1);

namespace Common\Workflow;

use JsonSerializable;

interface WorkflowState extends JsonSerializable
{
    /**
     * @param string $property The name of the state value to be found in the state
     *
     * @return bool
     */
    public function has(string $property): bool;

    /**
     * Reset the workflow to the beginning.
     *
     * This does not clear the name, date of birth or postcode as it is likely a repeat journey would use
     * identical information.
     */
    public function reset(): void;
}
