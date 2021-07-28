<?php

declare(strict_types=1);

namespace Common\Handler;

interface WorkflowStep
{
    public function isMissingPrerequisite(): bool;

    public function nextPage(): string;

    public function lastPage(): string;
}
