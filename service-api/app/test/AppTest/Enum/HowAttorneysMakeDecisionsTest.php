<?php

declare(strict_types=1);

namespace AppTest\Enum;

use App\Enum\HowAttorneysMakeDecisions;
use PHPUnit\Framework\TestCase;

class HowAttorneysMakeDecisionsTest extends TestCase
{
    public function test_from_discrete_booleans(): void
    {
        $result = HowAttorneysMakeDecisions::fromDiscreteBooleans(true, false, false);
        $this->assertEquals(HowAttorneysMakeDecisions::JOINTLY, $result);

        $result = HowAttorneysMakeDecisions::fromDiscreteBooleans(false, true, false);
        $this->assertEquals(HowAttorneysMakeDecisions::JOINTLY_AND_SEVERALLY, $result);

        $result = HowAttorneysMakeDecisions::fromDiscreteBooleans(false, false, true);
        $this->assertEquals(HowAttorneysMakeDecisions::JOINTLY_FOR_SOME_SEVERALLY_FOR_OTHERS, $result);

        $result = HowAttorneysMakeDecisions::fromDiscreteBooleans(false, false, false);
        $this->assertEquals(HowAttorneysMakeDecisions::SINGULAR, $result);
    }
}
