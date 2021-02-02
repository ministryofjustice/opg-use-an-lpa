<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use PHPUnit\Framework\TestCase;

abstract class LpaFixtureTestCase extends TestCase
{
    /**
     * Provides a fixture of a number of LPAs that need sorting.
     *
     * @return ArrayObject
     */
    protected function lpaFixtureData(): ArrayObject
    {
        // ---- Daniel Williams 3 LPAs
        $lpa1 = new Lpa();
        $lpa1->setUId('700000000001');
        $lpa1->setCaseSubtype('hw');
        $donor1 = new CaseActor();
        $donor1->setUId('700000000001');
        $donor1->setDob(new \DateTime('1980-01-01'));
        $donor1->setFirstname('Daniel');
        $donor1->setSurname('Williams');
        $lpa1->setDonor($donor1);
        $lpa1 = new ArrayObject(
            [
                'lpa' => $lpa1,
                'added' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            ArrayObject::ARRAY_AS_PROPS
        ); // added an hour ago

        $lpa5 = new Lpa();
        $lpa5->setUId('700000000005');
        $lpa5->setCaseSubtype('pfa');
        $lpa5->setDonor($donor1);
        $lpa5 = new ArrayObject(
            [
                'lpa' => $lpa5,
                'added' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
            ],
            ArrayObject::ARRAY_AS_PROPS
        ); // added 10 minutes ago

        $lpa6 = new Lpa();
        $lpa6->setUId('700000000006');
        $lpa6->setCaseSubtype('pfa');
        $lpa6->setDonor($donor1);
        $lpa6 = new ArrayObject(
            [
                'lpa' => $lpa6,
                'added' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
            ],
            ArrayObject::ARRAY_AS_PROPS
        ); // added five minutes ago

        // ---- Amy Johnson 2 LPAs
        $lpa2 = new Lpa();
        $lpa2->setUId('700000000002');
        $lpa2->setCaseSubtype('pfa');
        $donor2 = new CaseActor();
        $donor2->setUId('700000000002');
        $donor2->setDob(new \DateTime('1980-01-01'));
        $donor2->setFirstname('Amy');
        $donor2->setSurname('Johnson');
        $lpa2->setDonor($donor2);
        $lpa2 = new ArrayObject(
            [
                'lpa' => $lpa2,
                'added' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
            ],
            ArrayObject::ARRAY_AS_PROPS
        ); // added five minutes ago

        $lpa7 = new Lpa();
        $lpa7->setUId('700000000007');
        $lpa7->setCaseSubtype('pfa');
        $lpa7->setDonor($donor2);
        $lpa7 = new ArrayObject(
            [
                'lpa' => $lpa7,
                'added' => date('Y-m-d H:i:s', strtotime('-2 minutes'))
            ],
            ArrayObject::ARRAY_AS_PROPS
        ); // added two minutes ago

        // ---- Sam Taylor 2 LPAs
        $lpa3 = new Lpa();
        $lpa3->setUId('700000000003');
        $lpa3->setCaseSubtype('hw');
        $donor3 = new CaseActor();
        $donor3->setUId('700000000003');
        $donor3->setDob(new \DateTime('1980-01-01'));
        $donor3->setFirstname('Sam');
        $donor3->setSurname('Taylor');
        $lpa3->setDonor($donor3);
        $lpa3 = new ArrayObject(
            [
                'lpa' => $lpa3,
                'added' => date('Y-m-d H:i:s', strtotime('-3 hours'))
            ],
            ArrayObject::ARRAY_AS_PROPS
        ); // added an three hours ago

        $lpa4 = new Lpa();
        $lpa4->setUId('700000000004');
        $lpa4->setCaseSubtype('hw');
        $lpa4->setDonor($donor3);
        $lpa4 = new ArrayObject(
            [
                'lpa' => $lpa4,
                'added' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ],
            ArrayObject::ARRAY_AS_PROPS
        ); // added two hours ago

        // ---- Gemma Taylor 1 LPA (to test case if surname is same, donors are then ordered by firstname)
        $lpa8 = new Lpa();
        $lpa8->setUId('700000000008');
        $lpa8->setCaseSubtype('hw');
        $donor8 = new CaseActor();
        $donor8->setUId('700000000008');
        $donor8->setDob(new \DateTime('1980-01-01'));
        $donor8->setFirstname('Gemma');
        $donor8->setSurname('Taylor');
        $lpa8->setDonor($donor8);
        $lpa8 = new ArrayObject(
            [
                'lpa' => $lpa8,
                'added' => date('Y-m-d H:i:s', strtotime('-5 hours'))
            ],
            ArrayObject::ARRAY_AS_PROPS
        ); // added five hours ago

        // ---- Different donor!
        //      Gemma Taylor 1 LPA (to test case if donors with same name but different dob arent grouped)
        $lpa9 = new Lpa();
        $lpa9->setUId('700000000009');
        $lpa9->setCaseSubtype('hw');
        $donor9 = new CaseActor();
        $donor9->setUId('700000000009');
        $donor9->setDob(new \DateTime('1998-02-09'));
        $donor9->setFirstname('Gemma');
        $donor9->setSurname('Taylor');
        $lpa9->setDonor($donor9);
        $lpa9 = new ArrayObject(
            [
                'lpa' => $lpa9,
                'added' => date('Y-m-d H:i:s', strtotime('-9 hours'))
            ],
            ArrayObject::ARRAY_AS_PROPS
        ); // added nine hours ago

        return new ArrayObject(
            [
                '0001-01-01-01-111111' => $lpa1,
                '0002-01-01-01-222222' => $lpa2,
                '0003-01-01-01-333333' => $lpa3,
                '0004-01-01-01-444444' => $lpa4,
                '0005-01-01-01-555555' => $lpa5,
                '0006-01-01-01-666666' => $lpa6,
                '0007-01-01-01-777777' => $lpa7,
                '0008-01-01-01-888888' => $lpa8,
                '0009-01-01-01-999999' => $lpa9
            ],
            ArrayObject::ARRAY_AS_PROPS
        );
    }
}
