<?php

declare(strict_types=1);

namespace Actor\Workflow;

enum ActorType: string
{
    case DONOR    = 'donor';
    case ATTORNEY = 'attorney';
}
