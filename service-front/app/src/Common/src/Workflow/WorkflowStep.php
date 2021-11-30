<?php

declare(strict_types=1);

namespace Common\Workflow;

interface WorkflowStep
{
    /**
     * @return WorkflowState The workflow state object
     */
    public function state(): WorkflowState;

    /**
     * @return bool True if the user has the prerequisite workflow state items for this part of the workflow
     */
    public function isMissingPrerequisite(): bool;

    /**
     * @return string The route name of the next page in the workflow
     */
    public function nextPage(): string;

    /**
     * @return string The route name of the previous page in the workflow
     */
    public function lastPage(): string;
}
