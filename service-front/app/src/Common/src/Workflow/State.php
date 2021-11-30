<?php

declare(strict_types=1);

namespace Common\Workflow;

trait State
{
    /**
     * @return WorkflowState
     */
    public function state(): WorkflowState
    {
        $state = $this->session->get($this->stateFactory()::WORKFLOW_STATE);

        if ($state === null) {
            $state = ($this->stateFactory())();
            $this->session->set($this->stateFactory()::WORKFLOW_STATE, $state);
        }

        return $state;
    }
}
