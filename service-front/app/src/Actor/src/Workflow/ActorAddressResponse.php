<?php

declare(strict_types=1);

namespace Actor\Workflow;

enum ActorAddressResponse: string
{
    case YES      = 'Yes';
    case NO       = 'No';
    case NOT_SURE = 'Not sure';
}
