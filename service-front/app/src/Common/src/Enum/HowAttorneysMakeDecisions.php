<?php

declare(strict_types=1);

namespace Common\Enum;

enum HowAttorneysMakeDecisions: string
{
    case SINGULAR                              = 'singular';
    case JOINTLY                               = 'jointly';
    case JOINTLY_AND_SEVERALLY                 = 'jointly-and-severally';
    case JOINTLY_FOR_SOME_SEVERALLY_FOR_OTHERS = 'jointly-for-some-severally-for-others';

    public function isSingular(): bool {
        return $this === HowAttorneysMakeDecisions::SINGULAR;
    }

    public function isJointly(): bool {
        return $this === HowAttorneysMakeDecisions::JOINTLY;
    }

    public function isJointlyAndSeverally(): bool {
        return $this === HowAttorneysMakeDecisions::JOINTLY_AND_SEVERALLY;
    }

    public function isJointlyForSomeSeverallyForOthers(): bool {
        return $this === HowAttorneysMakeDecisions::JOINTLY_FOR_SOME_SEVERALLY_FOR_OTHERS;
    }
}
