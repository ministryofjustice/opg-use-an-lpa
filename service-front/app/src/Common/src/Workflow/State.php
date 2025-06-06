<?php

declare(strict_types=1);

namespace Common\Workflow;

use Common\Middleware\Workflow\StatePersistenceMiddleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @template T of WorkflowState
 */
trait State
{
    /**
     * @param ServerRequestInterface $request
     * @param class-string<T> $workflowStateClass
     * @return WorkflowState
     * @psalm-return WorkflowState<T>
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
