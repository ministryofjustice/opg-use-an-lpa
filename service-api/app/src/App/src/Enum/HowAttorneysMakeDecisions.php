<?php

declare(strict_types=1);

namespace App\Enum;

enum HowAttorneysMakeDecisions: string
{
    case SINGULAR                              = 'singular';
    case JOINTLY                               = 'jointly';
    case JOINTLY_AND_SEVERALLY                 = 'jointly-and-severally';
    case JOINTLY_FOR_SOME_SEVERALLY_FOR_OTHERS = 'jointly-for-some-severally-for-others';

    public static function fromDiscreteBooleans(
        bool $jointly,
        bool $jointlyAndSeverally,
        bool $jointlyForSomeAndSeverally,
    ): self {
        if ($jointly) {
            return self::JOINTLY;
        }

        if ($jointlyAndSeverally) {
            return self::JOINTLY_AND_SEVERALLY;
        }

        if ($jointlyForSomeAndSeverally) {
            return self::JOINTLY_FOR_SOME_SEVERALLY_FOR_OTHERS;
        }

        return self::SINGULAR;
    }
}
