<?php

declare(strict_types=1);

namespace Common\Workflow;

use Common\Middleware\Workflow\StatePersistenceMiddleware;
use Psr\Http\Message\ServerRequestInterface;

trait State
{
    /**
     * @param ServerRequestInterface      $request
     * @param class-string<WorkflowState> $workflowStateClass
     *
     * @return WorkflowState
     * @throws StateNotInitialisedException
     */
    public function loadState(ServerRequestInterface $request, string $workflowStateClass): WorkflowState
    {
        /** @var StatesCollection $states */
        $states = $request->getAttribute(StatePersistenceMiddleware::WORKFLOW_STATE_ATTRIBUTE);

        if ($states->has($workflowStateClass)) {
            return $states->get($workflowStateClass);
        }

        $state = new $workflowStateClass();
        $states->add($workflowStateClass, $state);

        return $state;
    }
}
