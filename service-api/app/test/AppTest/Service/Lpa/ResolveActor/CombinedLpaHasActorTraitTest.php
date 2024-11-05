<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\ResolveActor;

use App\Entity\LpaStore\LpaStore;
use App\Entity\LpaStore\LpaStoreAttorney;
use App\Entity\LpaStore\LpaStoreDonor;
use App\Entity\LpaStore\LpaStoreTrustCorporation;
use App\Entity\Sirius\SiriusLpa;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Entity\Sirius\SiriusLpaDonor;
use App\Entity\Sirius\SiriusLpaTrustCorporation;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Service\Lpa\ResolveActor\ActorType;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\ResolveActor\LpaActor;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CombinedLpaHasActorTraitTest extends TestCase
{
    private HasActorInterface $siriusMock;
    private HasActorInterface $lpaStoreMock;

    public function setUp(): void
    {
        $this->siriusMock = new SiriusLpa(
            applicationHasGuidance:     false,
            applicationHasRestrictions: false,
            applicationType:            'Classic',
            attorneyActDecisions:       null,
            attorneys:                  [
                new SiriusLpaAttorney(
                    addressLine1: '9 high street',
                    addressLine2: '',
                    addressLine3: '',
                    country:      '',
                    county:       '',
                    dob:          null,
                    email:        '',
                    firstname:    'A',
                    firstnames:   null,
                    id:           '345678901',
                    name:         null,
                    otherNames:   null,
                    postcode:     'DN37 5SH',
                    surname:      'B',
                    systemStatus: '1',
                    town:         '',
                    type:         'Primary',
                    uId:          '7345678901',
                ),
                new SiriusLpaAttorney(
                    addressLine1: '',
                    addressLine2: '',
                    addressLine3: '',
                    country:      '',
                    county:       '',
                    dob:          null,
                    email:        'XXXXX',
                    firstname:    'B',
                    firstnames:   null,
                    id:           '456789012',
                    name:         null,
                    otherNames:   null,
                    postcode:     '',
                    surname:      'C',
                    systemStatus: '1',
                    town:         '',
                    type:         'Primary',
                    uId:          '7456789012',
                ),
                new SiriusLpaAttorney(
                    addressLine1: '',
                    addressLine2: '',
                    addressLine3: '',
                    country:      '',
                    county:       '',
                    dob:          null,
                    email:        'XXXXX',
                    firstname:    'C',
                    firstnames:   null,
                    id:          '567890123',
                    name:         null,
                    otherNames:   null,
                    postcode:     '',
                    surname:      'D',
                    systemStatus: '1',
                    town:         '',
                    type:         'Primary',
                    uId:          '7567890123',
                ),
            ],
            caseSubtype:  LpaType::fromShortName('personal-welfare'),
            channel:      null,
            dispatchDate: null,
            donor:        new SiriusLpaDonor(
                addressLine1: '81 Front Street',
                addressLine2: 'LACEBY',
                addressLine3: '',
                country:      '',
                county:       '',
                dob:          null,
                email:        'RachelSanderson@opgtest.com',
                firstname:    'Rachel',
                firstnames:   null,
                id:          '123456789',
                linked:       [
                    [
                        'id'  => '123456789',
                        'uId' => '7123456789',
                    ],
                    [
                        'id'  => '234567890',
                        'uId' => '7234567890',
                    ],
                ],
                name:         null,
                otherNames:   null,
                postcode:     'DN37 5SH',
                surname:      'Sanderson',
                systemStatus: null,
                town:         '',
                type:         'Primary',
                uId:          '7123456789',
            ),
            hasSeveranceWarning:     null,
            invalidDate:             null,
            lifeSustainingTreatment: LifeSustainingTreatment::fromShortName('Option A'),
            lpaDonorSignatureDate:   new DateTimeImmutable('2012-12-12'),
            lpaIsCleansed:           true,
            onlineLpaId:             'A33718377316',
            receiptDate:             new DateTimeImmutable('2014-09-26'),
            registrationDate:        new DateTimeImmutable('2019-10-10'),
            rejectedDate:            null,
            replacementAttorneys:    [],
            status:                  'Registered',
            statusDate:              null,
            trustCorporations:       [
                new SiriusLpaTrustCorporation(
                    addressLine1: 'Street 1',
                    addressLine2: 'Street 2',
                    addressLine3: 'Street 3',
                    country:      'GB',
                    county:       'County',
                    dob:          null,
                    email:        null,
                    firstname:    'trust',
                    firstnames:   null,
                    id:           '678901234',
                    name:         'A',
                    otherNames:   null,
                    postcode:     'ABC 123',
                    surname:      'test',
                    systemStatus: '1',
                    town:         'Town',
                    type:         'Primary',
                    uId:          '7678901234',
                ),
                new SiriusLpaTrustCorporation(
                    addressLine1: 'Street 1',
                    addressLine2: 'Street 2',
                    addressLine3: 'Street 3',
                    country:      'GB',
                    county:       'County',
                    dob:          null,
                    email:        null,
                    firstname:    'trust',
                    firstnames:   null,
                    id:           '789012345',
                    name:         'B',
                    otherNames:   null,
                    postcode:     'ABC 123',
                    surname:      'test',
                    systemStatus: '1',
                    town:         'Town',
                    type:         'Primary',
                    uId:          '7789012345',
                ),
            ],
            uId:           '700000000047',
            withdrawnDate: null
        );

        $this->lpaStoreMock = new LpaStore(
            applicationHasGuidance:     false,
            applicationHasRestrictions: false,
            applicationType:            'Classic',
            attorneyActDecisions:       null,
            attorneys:                  [
                new LpaStoreAttorney(
                    addressLine1: '9 high street',
                    addressLine2: '',
                    addressLine3: '',
                    country:      '',
                    county:       '',
                    dob:          null,
                    email:        '',
                    firstname:    'A',
                    firstnames:   null,
                    name:         null,
                    otherNames:   null,
                    postcode:     'DN37 5SH',
                    surname:      'B',
                    systemStatus: '1',
                    town:         '',
                    type:         'Primary',
                    uId:          '7345678901',
                ),
                new LpaStoreAttorney(
                    addressLine1: '',
                    addressLine2: '',
                    addressLine3: '',
                    country:      '',
                    county:       '',
                    dob:          null,
                    email:        'XXXXX',
                    firstname:    'B',
                    firstnames:   null,
                    name:         null,
                    otherNames:   null,
                    postcode:     '',
                    surname:      'C',
                    systemStatus: '1',
                    town:         '',
                    type:         'Primary',
                    uId:          '7456789012',
                ),
                new LpaStoreAttorney(
                    addressLine1: '',
                    addressLine2: '',
                    addressLine3: '',
                    country:      '',
                    county:       '',
                    dob:          null,
                    email:        'XXXXX',
                    firstname:    'C',
                    firstnames:   null,
                    name:         null,
                    otherNames:   null,
                    postcode:     '',
                    surname:      'D',
                    systemStatus: '1',
                    town:         '',
                    type:         'Primary',
                    uId:          '7567890123',
                ),
            ],
            caseSubtype:  LpaType::fromShortName('personal-welfare'),
            channel:      null,
            dispatchDate: null,
            donor:        new LpaStoreDonor(
                addressLine1: '81 Front Street',
                addressLine2: 'LACEBY',
                addressLine3: '',
                country:      '',
                county:       '',
                dob:          null,
                email:        'RachelSanderson@opgtest.com',
                firstname:    'Rachel',
                firstnames:   null,
                name:         null,
                otherNames:   null,
                postcode:     'DN37 5SH',
                surname:      'Sanderson',
                systemStatus: null,
                town:         '',
                type:         'Primary',
                uId:          '7123456789',
            ),
            hasSeveranceWarning:     null,
            invalidDate:             null,
            lifeSustainingTreatment: LifeSustainingTreatment::fromShortName('Option A'),
            lpaDonorSignatureDate:   new DateTimeImmutable('2012-12-12'),
            lpaIsCleansed:           true,
            onlineLpaId:             'A33718377316',
            receiptDate:             new DateTimeImmutable('2014-09-26'),
            registrationDate:        new DateTimeImmutable('2019-10-10'),
            rejectedDate:            null,
            replacementAttorneys:    [],
            status:                  'Registered',
            statusDate:              null,
            trustCorporations:       [
                new LpaStoreTrustCorporation(
                    addressLine1: 'Street 1',
                    addressLine2: 'Street 2',
                    addressLine3: 'Street 3',
                    country:      'GB',
                    county:       'County',
                    dob:          null,
                    email:        null,
                    firstname:    'trust',
                    firstnames:   null,
                    name:         'A',
                    otherNames:   null,
                    postcode:     'ABC 123',
                    surname:      'test',
                    systemStatus: '1',
                    town:         'Town',
                    type:         'Primary',
                    uId:          '7678901234',
                ),
                new LpaStoreTrustCorporation(
                    addressLine1: 'Street 1',
                    addressLine2: 'Street 2',
                    addressLine3: 'Street 3',
                    country:      'GB',
                    county:       'County',
                    dob:          null,
                    email:        null,
                    firstname:    'trust',
                    firstnames:   null,
                    name:         'B',
                    otherNames:   null,
                    postcode:     'ABC 123',
                    surname:      'test',
                    systemStatus: '1',
                    town:         'Town',
                    type:         'Primary',
                    uId:          '7789012345',
                ),
            ],
            uId:           '700000000047',
            withdrawnDate: null
        );
    }

    #[Test]
    public function does_not_find_nonexistent_actor(): void
    {
        $result = $this->siriusMock->hasActor('012345678');
        $this->assertNull($result);

        $result = $this->lpaStoreMock->hasActor('012345678');
        $this->assertNull($result);
    }

    #[Test]
    public function finds_a_donor_actor(): void
    {
        $result = $this->siriusMock->hasActor('7123456789');
        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals(ActorType::DONOR, $result->actorType);

        $result = $this->lpaStoreMock->hasActor('7123456789');
        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals(ActorType::DONOR, $result->actorType);
    }

    #[Test]
    public function finds_a_donor_actor_by_id(): void
    {
        $result = $this->siriusMock->hasActor('123456789');
        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals(ActorType::DONOR, $result->actorType);

        $result = $this->lpaStoreMock->hasActor('123456789');
        $this->assertNull($result);

        $result = $this->lpaStoreMock->hasActor('7123456789');
        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals(ActorType::DONOR, $result->actorType);
    }

    #[Test]
    public function finds_an_attorney_actor(): void
    {
        $result = $this->siriusMock->hasActor('7456789012');
        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor->firstname);
        $this->assertEquals(ActorType::ATTORNEY, $result->actorType);

        $result = $this->lpaStoreMock->hasActor('7456789012');
        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor->firstname);
        $this->assertEquals(ActorType::ATTORNEY, $result->actorType);
    }

    #[Test]
    public function finds_an_attorney_actor_by_id(): void
    {
        $result = $this->siriusMock->hasActor('567890123');
        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('C', $result->actor->firstname);
        $this->assertEquals(ActorType::ATTORNEY, $result->actorType);

        $result = $this->lpaStoreMock->hasActor('567890123');
        $this->assertNull($result);

        $result = $this->lpaStoreMock->hasActor('7567890123');
        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('C', $result->actor->firstname);
        $this->assertEquals(ActorType::ATTORNEY, $result->actorType);
    }

    #[Test]
    public function finds_a_trust_corporation_actor(): void
    {
        $result = $this->siriusMock->hasActor('678901234');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('A', $result->actor->name);
        $this->assertEquals(ActorType::TRUST_CORPORATION, $result->actorType);
    }

    #[Test]
    public function finds_a_trust_corporation_actor_by_id(): void
    {
        $result = $this->siriusMock->hasActor('789012345');
        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor->name);
        $this->assertEquals(ActorType::TRUST_CORPORATION, $result->actorType);

        $result = $this->lpaStoreMock->hasActor('789012345');
        $this->assertNull($result);

        $result = $this->lpaStoreMock->hasActor('7789012345');
        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor->name);
        $this->assertEquals(ActorType::TRUST_CORPORATION, $result->actorType);
    }
}
