<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Entity\Sirius\SiriusLpa as CombinedSiriusLpa;
use App\Service\Lpa\SiriusLpa;
use App\Entity\Sirius\SiriusLpaDonor;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Service\Lpa\AddLpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\Lpa\SiriusPerson;
use DateTimeImmutable;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

#[CoversClass(LpaAlreadyAdded::class)]
class LpaAlreadyAddedTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private LpaManagerInterface|ObjectProphecy $lpaManagerProphecy;
    private UserLpaActorMapInterface|ObjectProphecy $userLpaActorMapProphecy;

    private string $userId;
    private string $lpaUid;
    private string $userLpaActorToken;

    public function setUp(): void
    {
        $this->lpaManagerProphecy      = $this->prophesize(LpaManagerInterface::class);
        $this->userLpaActorMapProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $this->loggerProphecy            = $this->prophesize(LoggerInterface::class);

        $this->userId            = '12345';
        $this->lpaUid            = '700000000543';
        $this->userLpaActorToken = 'abc123-456rtp';
    }

    private function getLpaAlreadyAddedService(): LpaAlreadyAdded
    {
        return new LpaAlreadyAdded(
            $this->lpaManagerProphecy->reveal(),
            $this->userLpaActorMapProphecy->reveal()
        );
    }

    #[Test]
    public function returns_null_if_lpa_not_already_added(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn([]);

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, '700000000321');
        $this->assertNull($lpaAddedData);
    }

    #[Test]
    public function returns_not_activated_flag_if_lpa_requested_but_not_active(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'         => $this->userLpaActorToken,
                        'SiriusUid'  => $this->lpaUid,
                        'ActivateBy' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn(
                [
                    'user-lpa-actor-token' => $this->userLpaActorToken,
                    'lpa'                  => $this->getLpaDataFixtureNew(),
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals(
            [
                'donor'                => [
                    'uId'         => '700000000444',
                    'firstname'   => 'Another',
                    'middlenames' => '',
                    'surname'     => 'Person',
                ],
                'caseSubtype'          => 'hw',
                'lpaActorToken'        => $this->userLpaActorToken,
                'notActivated'         => true,
                'activationKeyDueDate' => null,
            ],
            $lpaAddedData
        );
    }

    #[Test]
    public function returns_null_if_lpa_added_but_not_usable_found_in_api(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'        => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn([]);

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertNull($lpaAddedData);
    }

    #[Test]
    public function returns_lpa_data_if_lpa_is_already_added_combined(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'        => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn(
                [
                    'user-lpa-actor-token' => $this->userLpaActorToken,
                    'lpa'                  => $this->getLpaDataFixtureNew(),
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals(
            [
                'donor'                => [
                    'uId'         => '700000000444',
                    'firstname'   => 'Another',
                    'middlenames' => '',
                    'surname'     => 'Person',
                ],
                'caseSubtype'          => 'hw',
                'lpaActorToken'        => $this->userLpaActorToken,
                'activationKeyDueDate' => null,
            ],
            $lpaAddedData
        );
    }

    #[Test]
    public function returns_lpa_data_if_lpa_is_already_added_old(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'        => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn(
                [
                    'user-lpa-actor-token' => $this->userLpaActorToken,
                    'lpa'                  => $this->getLpaDataFixtureOld(),
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals(
            [
                'donor'                => [
                    'uId'         => '700000001111',
                    'firstname'   => 'Donor',
                    'middlenames' => '',
                    'surname'     => 'Person',
                ],
                'caseSubtype'          => 'hw',
                'lpaActorToken'        => $this->userLpaActorToken,
                'activationKeyDueDate' => null,
            ],
            $lpaAddedData
        );
    }


    #[Test]
    public function correctly_handles_records_without_sirius_uids_if_lpa_already_added(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'     => '0123-01-01-01-01234',
                        'LpaUid' => 'M-1234-1234-1234',
                    ],
                    [
                        'Id'        => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn(
                [
                    'user-lpa-actor-token' => $this->userLpaActorToken,
                    'lpa'                  => $this->getLpaDataFixtureNew(),
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals(
            [
                'donor'                => [
                    'uId'         => '700000000444',
                    'firstname'   => 'Another',
                    'middlenames' => '',
                    'surname'     => 'Person',
                ],
                'caseSubtype'          => 'hw',
                'lpaActorToken'        => $this->userLpaActorToken,
                'activationKeyDueDate' => null,
            ],
            $lpaAddedData
        );
    }

    #[Test]
    public function returns_null_if_different_lpa_added(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'        => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->shouldNotBeCalled();

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, '712312341234');
        $this->assertNull($lpaAddedData);
    }

    private function getLpaDataFixtureNew(): CombinedSiriusLpa
    {
        return new CombinedSiriusLpa(
            applicationHasGuidance:                    false,
            applicationHasRestrictions:                false,
            applicationType:                           'Classic',
            attorneys:                                 [],
            caseAttorneyJointly:                       true,
            caseAttorneyJointlyAndJointlyAndSeverally: false,
            caseAttorneyJointlyAndSeverally:           false,
            caseSubtype:                               LpaType::PERSONAL_WELFARE,
            channel:                                   null,
            dispatchDate:                              null,
            donor:                                     new SiriusLpaDonor(
                                                           addressLine1: '81 Front Street',
                                                           addressLine2: 'xxxxx',
                                                           addressLine3: '',
                                                           country:      '',
                                                           county:       '',
                                                           dob:          null,
                                                           email:        'AnotherPerson@opgtest.com',
                                                           firstname:    'Another',
                                                           id:           '123456789',
                                                           linked:       [],
                                                           middlenames:  null,
                                                           otherNames:   null,
                                                           postcode:     'DN37 5SH',
                                                           surname:      'Person',
                                                           systemStatus: null,
                                                           town:         '',
                                                           uId:          '700000000444',
                                                       ),
            hasSeveranceWarning:                       null,
            invalidDate:                               null,
            lifeSustainingTreatment:                   LifeSustainingTreatment::OPTION_A,
            lpaDonorSignatureDate:                     new DateTimeImmutable('2012-12-12'),
            lpaIsCleansed:                             true,
            onlineLpaId:                               'A33718377316',
            receiptDate:                               new DateTimeImmutable('2014-09-26'),
            registrationDate:                          new DateTimeImmutable('2019-10-10'),
            rejectedDate:                              null,
            replacementAttorneys:                      [],
            status:                                    'Registered',
            statusDate:                                null,
            trustCorporations:                         [],
            uId:                                       '700000000047',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );
    }

    private function getLpaDataFixtureOld(): SiriusLpa
    {
        return new SiriusLpa(
            [
                'uId'       => '700000012346',
                'caseSubtype' => 'hw',
                'donor'     => $this->donorFixtureOld(),
                'attorneys' => [],
            ],
            $this->loggerProphecy->reveal(),
        );
    }

    private static function donorFixtureOld(): SiriusPerson
    {
        return new SiriusPerson(
            [
                'uId'       => '700000001111',
                'dob'       => '1975-10-05',
                'firstname' => 'Donor',
                'surname'   => 'Person',
                'addresses' => [
                    [
                        'postcode' => 'PY1 3Kd',
                    ],
                ],
            ],
            new Logger('test-output'),
        );
    }
}


