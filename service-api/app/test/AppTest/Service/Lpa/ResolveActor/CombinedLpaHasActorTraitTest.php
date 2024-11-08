<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\ResolveActor;

use App\Entity\Sirius\SiriusLpa;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Enum\WhenTheLpaCanBeUsed;
use App\Service\Lpa\ResolveActor\ActorType;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\ResolveActor\LpaActor;
use App\Service\Lpa\ResolveActor\SiriusHasActorTrait;
use App\Service\Lpa\SiriusPerson;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CombinedLpaHasActorTraitTest extends TestCase
{
    private HasActorInterface $mock;

    public function setUp(): void
    {
        $this->mock                     = new SiriusLpa(
            applicationHasGuidance     : false,
            applicationHasRestrictions : false,
            applicationType            : 'Classic',
            attorneys                  : [
                [
                    'addressLine1' => '9 high street',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'dob'          => null,
                    'email'        => '',
                    'firstname'    => 'A',
                    'firstnames'   => null,
                    'name'         => null,
                    'otherNames'   => null,
                    'postcode'     => 'DN37 5SH',
                    'surname'      => 'B',
                    'systemStatus' => '1',
                    'town'         => '',
                    'type'         => 'Primary',
                    'uId'          => '345678901',
                ],
                [
                    'addressLine1' => '',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'dob'          => null,
                    'email'        => 'XXXXX',
                    'firstname'    => 'B',
                    'firstnames'   => null,
                    'name'         => null,
                    'otherNames'   => null,
                    'postcode'     => '',
                    'surname'      => 'C',
                    'systemStatus' => '1',
                    'town'         => '',
                    'type'         => 'Primary',
                    'uId'          => '456789012',
                ],
                [
                    'addressLine1' => '',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'dob'          => null,
                    'email'        => 'XXXXX',
                    'firstname'    => 'C',
                    'firstnames'   => null,
                    'name'         => null,
                    'otherNames'   => null,
                    'postcode'     => '',
                    'surname'      => 'D',
                    'systemStatus' => '1',
                    'town'         => '',
                    'type'         => 'Primary',
                    'uId'          => '567890123',
                ],
            ],
            caseSubtype                : LpaType::fromShortName('personal-welfare'),
            channel                    : null,
            dispatchDate               : null,
            donor                      : (object)[
                'addressLine1' => '81 Front Street',
                'addressLine2' => 'LACEBY',
                'addressLine3' => '',
                'country'      => '',
                'county'       => '',
                'dob'          => null,
                'email'        => 'RachelSanderson@opgtest.com',
                'firstname'    => 'Rachel',
                'firstnames'   => null,
                'name'         => null,
                'otherNames'   => null,
                'postcode'     => 'DN37 5SH',
                'surname'      => 'Sanderson',
                'systemStatus' => null,
                'town'         => '',
                'type'         => 'Primary',
                'uId'          => '123456789',
                'linked'       => [
                    [
                        'id'  => 1,
                        'uId' => '123456789',
                    ],
                    [
                        'id'  => 2,
                        'uId' => '234567890',
                    ],
                ],
            ],
            hasSeveranceWarning        : null,
            howAttorneysMakeDecisions  : null,
            invalidDate                : null,
            lifeSustainingTreatment    : LifeSustainingTreatment::fromShortName('Option A'),
            lpaDonorSignatureDate      : new DateTimeImmutable('2012-12-12'),
            lpaIsCleansed              : true,
            onlineLpaId                : 'A33718377316',
            receiptDate                : new DateTimeImmutable('2014-09-26'),
            registrationDate           : new DateTimeImmutable('2019-10-10'),
            rejectedDate               : null,
            replacementAttorneys       : [],
            status                     : 'Registered',
            statusDate                 : null,
            trustCorporations          : [
                [
                    'addressLine1' => 'Street 1',
                    'addressLine2' => 'Street 2',
                    'addressLine3' => 'Street 3',
                    'country'      => 'GB',
                    'county'       => 'County',
                    'dob'          => null,
                    'email'        => null,
                    'firstname'    => 'trust',
                    'firstnames'   => null,
                    'name'         => 'A',
                    'otherNames'   => null,
                    'postcode'     => 'ABC 123',
                    'surname'      => 'test',
                    'systemStatus' => '1',
                    'town'         => 'Town',
                    'type'         => 'Primary',
                    'uId'          => '678901234',
                ],
                [
                    'addressLine1' => 'Street 1',
                    'addressLine2' => 'Street 2',
                    'addressLine3' => 'Street 3',
                    'country'      => 'GB',
                    'county'       => 'County',
                    'dob'          => null,
                    'email'        => null,
                    'firstname'    => 'trust',
                    'firstnames'   => null,
                    'name'         => 'B',
                    'otherNames'   => null,
                    'postcode'     => 'ABC 123',
                    'surname'      => 'test',
                    'systemStatus' => '1',
                    'town'         => 'Town',
                    'type'         => 'Primary',
                    'uId'          => '789012345',
                ],
            ],
            uId                        : '700000000047',
            withdrawnDate              : null,
            whenTheLpaCanBeUsed    : WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST
        );
    }

    #[Test]
    public function does_not_find_nonexistant_actor(): void
    {
        $result = $this->mock->hasActor('012345678');

        $this->assertNull($result);
    }

    #[Test]
    public function finds_a_donor_actor(): void
    {
        $result = $this->mock->hasActor('123456789');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals(ActorType::DONOR, $result->actorType);
    }

    #[Test]
    public function finds_an_attorney_actor(): void
    {
        $result = $this->mock->hasActor('456789012');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor['firstname']);
        $this->assertEquals(ActorType::ATTORNEY, $result->actorType);
    }

    #[Test]
    public function finds_a_trust_corporation_actor(): void
    {
        $result = $this->mock->hasActor('789012345');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor['name']);
        $this->assertEquals(ActorType::TRUST_CORPORATION, $result->actorType);
    }
}
