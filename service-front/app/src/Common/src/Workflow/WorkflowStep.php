<?php

declare(strict_types=1);

namespace Common\Workflow;

use Psr\Http\Message\ServerRequestInterface;

interface WorkflowStep
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return WorkflowState The workflow state object associated with this step
     * @throws StateNotInitialisedException
     */
    public function state(ServerRequestInterface $request): WorkflowState;

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool True if the user has the prerequisite workflow state items for this part of the workflow
     */
    public function isMissingPrerequisite(ServerRequestInterface $request): bool;

    /**
     * @param WorkflowState $state
     *
     * @return string The route name of the next page in the workflow
     */
    public function nextPage(WorkflowState $state): string;

    /**
     * @param WorkflowState $state
     *
     * @return string The route name of the previous page in the workflow
     */
    public function lastPage(WorkflowState $state): string;
}
