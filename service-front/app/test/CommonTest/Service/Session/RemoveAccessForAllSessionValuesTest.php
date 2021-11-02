<?php

namespace CommonTest\Service\Session;

use Common\Service\Session\RemoveAccessForAllSessionValues;
use Mezzio\Session\SessionInterface;
use Monolog\Test\TestCase;

class RemoveAccessForAllSessionValuesTest extends TestCase
{
    /** @test */
    public function successfully_removes_post_lpa_match_session_values(): void
    {
        $removeSessionValues = new RemoveAccessForAllSessionValues();

        $sessionProphecy = $this->prophesize(SessionInterface::class);

        $postMatchSessionValues = [
            'actor_id',
            'actor_role',
            'donor_first_names',
            'donor_last_name',
            'donor_dob',
            'telephone_option',
            'lpa_full_match_but_not_cleansed'
        ];

        foreach ($postMatchSessionValues as $sessionValue) {
            $sessionProphecy->unset($sessionValue)->shouldBeCalled();
        }

        $removeSessionValues->removePostLPAMatchSessionValues($sessionProphecy->reveal());
    }

    /** @test */
    public function successfully_cleans_all_access_for_all_session_values(): void
    {
        $removeSessionValues = new RemoveAccessForAllSessionValues();

        $sessionProphecy = $this->prophesize(SessionInterface::class);

        $toRemoveSessionValues = [
            'opg_reference_number',
            'actor_id',
            'actor_role',
            'donor_first_names',
            'donor_last_name',
            'donor_dob',
            'telephone_option',
            'lpa_full_match_but_not_cleansed'
        ];

        foreach ($toRemoveSessionValues as $sessionValue) {
            $sessionProphecy->unset($sessionValue)->shouldBeCalled();
        }

        $removeSessionValues->cleanAccessForAllSessionValues($sessionProphecy->reveal());
    }
}

