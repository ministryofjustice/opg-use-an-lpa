<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Enum\Channel;
use Common\Enum\HowAttorneysMakeDecisions;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use Common\Enum\WhenTheLpaCanBeUsed;
use Common\Service\Lpa\Factory\LpaDataFormatter;
use CommonTest\Helper\EntityTestHelper;
use CommonTest\Helper\TestData;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CombinedLpaTest extends TestCase
{
    use ProphecyTrait;

    private LpaDataFormatter $lpaDataFormatter;

    public function setUp(): void
    {
        $this->lpaDataFormatter = new LpaDataFormatter();
    }

    #[Test]
    public function can_test_getters()
    {
        $donor = EntityTestHelper::makePerson(
            uId: 'donor'
        );

        $attorneys = [
            EntityTestHelper::makePerson(
                uId: 'attorney'
            ),
        ];

        $trustCorporations = [
            EntityTestHelper::makePerson(
                uId: 'trust-corporation'
            ),
        ];

        $combinedLpa = EntityTestHelper::makeCombinedLpa(
            applicationHasGuidance:     true,
            applicationHasRestrictions: true,
            attorneys:                  $attorneys,
            donor:                      $donor,
            howAttorneysMakeDecisions:  HowAttorneysMakeDecisions::JOINTLY,
            lifeSustainingTreatment:    LifeSustainingTreatment::OPTION_B,
            lpaDonorSignatureDate:      new DateTimeImmutable(TestData::TESTDATESTRING),
            status:                     'status',
            trustCorporations:          $trustCorporations,
            uId:                        '123',
            whenTheLpaCanBeUsed:        WhenTheLpaCanBeUsed::WHEN_HAS_CAPACITY
        );

        $this->assertEquals(true, $combinedLpa->getApplicationHasGuidance());
        $this->assertEquals(true, $combinedLpa->getApplicationHasRestrictions());
        $this->assertEquals($attorneys, $combinedLpa->getActiveAttorneys());
        $this->assertEquals($donor, $combinedLpa->getDonor());
        $this->assertEquals(HowAttorneysMakeDecisions::JOINTLY, $combinedLpa->getHowAttorneysMakeDecisions());
        $this->assertEquals(false, $combinedLpa->getCaseAttorneySingular());
        $this->assertEquals(true, $combinedLpa->getCaseAttorneyJointly());
        $this->assertEquals(false, $combinedLpa->getCaseAttorneyJointlyAndSeverally());
        $this->assertEquals(false, $combinedLpa->getCaseAttorneyJointlyAndJointlyAndSeverally());
        $this->assertEquals(LifeSustainingTreatment::OPTION_B->value, $combinedLpa->getLifeSustainingTreatment());
        $this->assertEquals(new DateTimeImmutable(TestData::TESTDATESTRING), $combinedLpa->getLpaDonorSignatureDate());
        $this->assertEquals('status', $combinedLpa->getStatus());
        $this->assertEquals($trustCorporations, $combinedLpa->getTrustCorporations());
        $this->assertEquals('123', $combinedLpa->getUId());
        $this->assertEquals(WhenTheLpaCanBeUsed::WHEN_HAS_CAPACITY, $combinedLpa->getWhenTheLpaCanBeUsed());
        $this->assertEquals($attorneys, $combinedLpa->getAttorneys());
        $this->assertEquals(LpaType::PERSONAL_WELFARE, $combinedLpa->getLpaType());
        $this->assertEquals(Channel::ONLINE, $combinedLpa->getChannel());
    }
}
