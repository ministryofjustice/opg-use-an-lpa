<?php

declare(strict_types=1);

namespace Common\Workflow;

use Psr\Http\Message\ServerRequestInterface;

interface WorkflowStep
{
    /**
     * Provide access to the correct workflow state object for this particular workflow.
     *
     * @param ServerRequestInterface $request
     * @return WorkflowState The workflow state object associated with this step
     * @throws StateNotInitialisedException
     */
    public function state(ServerRequestInterface $request): WorkflowState;

    /**
     * Check the workflow state object for expected data and return false if there are missing items.
     *
     * @param ServerRequestInterface $request
     * @return bool True if the user has the prerequisite workflow state items for this part of the workflow
     * @throws StateNotInitialisedException
     */
    public function isMissingPrerequisite(ServerRequestInterface $request): bool;

    /**
     * Using the provided workflow state decide what step to execute next
     *
     * @param WorkflowState $state
     * @return string The route name of the next page in the workflow
     */
    public function nextPage(WorkflowState $state): string;

    /**
     * Using the provided workflow state decide what the previous step is.
     *
     * It's important to note that this might not always be the 'previous' page as a browser might see it.
     *
     * @param WorkflowState $state
     * @return string The route name of the previous page in the workflow
     */
    public function lastPage(WorkflowState $state): string;
}
