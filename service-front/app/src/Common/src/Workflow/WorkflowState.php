<?php

declare(strict_types=1);

namespace Common\Workflow;

use JsonSerializable;

interface WorkflowState extends JsonSerializable
{
    /**
     * Reset the workflow to the beginning.
     *
     * This does not clear the name, date of birth or postcode as it is likely a repeat journey would use
     * identical information.
     */
    public function reset(): void;
}
