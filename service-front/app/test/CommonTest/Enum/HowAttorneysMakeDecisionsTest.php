<?php

declare(strict_types=1);

namespace CommonTest\Enum;

use Common\Enum\HowAttorneysMakeDecisions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HowAttorneysMakeDecisionsTest extends TestCase
{
    #[Test]
    public function test_how_attorneys_make_decisions_enum_has_issers(): void
    {
        $singular = HowAttorneysMakeDecisions::SINGULAR;
        $jointly = HowAttorneysMakeDecisions::JOINTLY;
        $jointlyAndSeverally = HowAttorneysMakeDecisions::JOINTLY_AND_SEVERALLY;
        $jointlyForSome = HowAttorneysMakeDecisions::JOINTLY_FOR_SOME_SEVERALLY_FOR_OTHERS;

        $this->assertTrue($singular->isSingular());
        $this->assertFalse($singular->isJointly());

        $this->assertTrue($jointly->isJointly());
        $this->assertFalse($jointly->isSingular());

        $this->assertTrue($jointlyAndSeverally->isJointlyAndSeverally());
        $this->assertFalse($jointlyAndSeverally->isSingular());

        $this->assertTrue($jointlyForSome->isJointlyForSomeSeverallyForOthers());
        $this->assertFalse($jointlyForSome->isSingular());

    }
}
