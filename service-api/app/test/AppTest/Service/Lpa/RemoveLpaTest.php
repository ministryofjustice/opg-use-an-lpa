<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\Response\LpaInterface;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Entity\Sirius\SiriusLpa as CombinedSiriusLpa;
use App\Entity\Sirius\SiriusLpaDonor;
use App\Enum\LifeSustainingTreatment;
use App\Enum\LpaType;
use App\Exception\ApiException;
use App\Exception\NotFoundException;
use App\Service\Lpa\RemoveLpa;
use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\SiriusLpaManager;
use App\Service\Lpa\SiriusPerson;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use App\Service\Lpa\LpaManagerInterface;
use DateTimeImmutable;

class RemoveLpaTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private SiriusLpaManager|ObjectProphecy $lpaServiceProphecy;
    private UserLpaActorMapInterface|ObjectProphecy $userLpaActorMapInterfaceProphecy;
    private ViewerCodesInterface|ObjectProphecy $viewerCodesInterfaceProphecy;
    private LpaManagerInterface|ObjectProphecy $lpaManagerProphecy;

    private string $actorLpaToken;
    private Lpa $lpa;
    private string $lpaUid;
    private array $deletedData;
    private array $userActorLpa;
    private string $userId;
    private array $viewerCodes;

    public function setUp(): void
    {
        $this->loggerProphecy                   = $this->prophesize(LoggerInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $this->viewerCodesInterfaceProphecy     = $this->prophesize(ViewerCodesInterface::class);
        $this->lpaServiceProphecy               = $this->prophesize(SiriusLpaManager::class);
        $this->lpaManagerProphecy               = $this->prophesize(LpaManagerInterface::class);

        $this->lpaUid        = '700000055554';
        $this->actorLpaToken = '2345Token0123';
        $this->userId        = '1234-0000-1234-0000';

        $this->userActorLpa = [
            'SiriusUid' => $this->lpaUid,
            'Added'     => (new DateTime())->modify('-6 months')->format('Y-m-d'),
            'Id'        => $this->actorLpaToken,
            'ActorId'   => 1,
            'UserId'    => $this->userId,
        ];

        $this->viewerCodes = [
            0 => [ // this code is active
                'Id'           => '1',
                'ViewerCode'   => '123ABCD6789R',
                'SiriusUid'    => $this->lpaUid,
                'Added'        => (new DateTime())->format('Y-m-d'),
                'Expires'      => (new DateTime())->modify('+1 month')->format('Y-m-d'),
                'UserLpaActor' => $this->actorLpaToken,
                'Organisation' => 'Some Organisation',
            ],
            1 => [ // this code has expired
                'Id'           => '2',
                'ViewerCode'   => 'YG41BCD693FH',
                'SiriusUid'    => $this->lpaUid,
                'Added'        => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                'Expires'      => (new DateTime())->modify('-1 month')->format('Y-m-d'),
                'UserLpaActor' => $this->actorLpaToken,
                'Organisation' => 'Some Organisation 2',
            ],
            2 => [ // this code is already cancelled
                'Id'           => '3',
                'ViewerCode'   => 'RL2AD1936KV2',
                'SiriusUid'    => $this->lpaUid,
                'Added'        => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                'Expires'      => (new DateTime())->modify('-1 month')->format('Y-m-d'),
                'Cancelled'    => (new DateTime())->modify('-2 months')->format('Y-m-d'),
                'UserLpaActor' => $this->actorLpaToken,
                'Organisation' => 'Some Organisation 3',
            ],
        ];

        $this->deletedData = [
            'Id'        => $this->actorLpaToken,
            'SiriusUid' => $this->lpaUid,
            'Added'     => (new DateTime())->modify('-6 months')->format('Y-m-d'),
            'ActorId'   => '1',
            'UserId'    => $this->userId,
        ];

        $this->lpaRemovedData = new Lpa(
            $this->getLpaDataFixtureOld(),
            new DateTime()
        );

        $this->combinedLpaRemovedData = new Lpa(
            $this->getLpaDataFixtureNew(),
            new DateTime()
        );

        $this->old_lpa_response = [
            'donor' => [
                'uId'           => $this->getLpaDataFixtureOld()->getDonor()->getUid(),
                'firstname'     => $this->getLpaDataFixtureOld()->getDonor()->getFirstnames(),
                'middlenames'   => isset(
                    $this->getLpaDataFixtureOld()->getDonor()['middlenames']
                ) ? $this->getLpaDataFixtureOld()->getDonor()->getMiddleNames() : '',
                'surname'       => $this->getLpaDataFixtureOld()->getDonor()->getSurname(),
            ],
            'caseSubtype' => $this->getLpaDataFixtureOld()->getCaseSubType(),
        ];

        $this->new_lpa_response = [
            'donor' => [
                'uId'           => $this->getLpaDataFixtureNew()->getDonor()->getUid(),
                'firstname'     => $this->getLpaDataFixtureNew()->getDonor()->getFirstnames(),
                'middlenames'   => isset(
                    $this->getLpaDataFixtureNew()->getDonor()->getMiddleNames()->value
                ) ? $this->getLpaDataFixtureNew()->getDonor()->getMiddleNames() : '',
                'surname'       => $this->getLpaDataFixtureNew()->getDonor()->getSurname(),
            ],
            'caseSubtype' => $this->getLpaDataFixtureNew()->getCaseSubType(),
        ];
    }

    #[Test]
    public function it_can_remove_lpa_from_a_user_account_with_no_viewer_codes_to_update(): void
    {
        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn($this->userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->getCodesByLpaId($this->userActorLpa['SiriusUid'])
            ->willReturn([]);

        $this->lpaManagerProphecy
            ->getByUid($this->userActorLpa['SiriusUid'], $this->userActorLpa['UserId'])
            ->willReturn($this->lpaRemovedData);

        $this->userLpaActorMapInterfaceProphecy
            ->delete($this->actorLpaToken)
            ->willReturn($this->deletedData);

        $result = ($this->deleteLpa())($this->userId, $this->actorLpaToken);

        $this->assertNotEmpty($result);
        $this->assertEquals($result, $this->old_lpa_response);
    }

    #[Test]
    public function it_can_remove_new_format_lpa_from_a_user_account_with_no_viewer_codes_to_update(): void
    {
        $userActorLpa = [
            'LpaUid' => 'M-789Q-P4DF-4UX3',
            'Added'     => (new DateTime())->modify('-6 months')->format('Y-m-d'),
            'Id'        => $this->actorLpaToken,
            'ActorId'   => '1',
            'UserId'    => $this->userId,
        ];

        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn($userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->getCodesByLpaId($userActorLpa['LpaUid'])
            ->willReturn([]);

        $this->lpaManagerProphecy
            ->getByUid($userActorLpa['LpaUid'], $userActorLpa['UserId'])
            ->willReturn($this->combinedLpaRemovedData);

        $this->userLpaActorMapInterfaceProphecy
            ->delete($this->actorLpaToken)
            ->willReturn($this->deletedData);

        $result = ($this->deleteLpa())($this->userId, $this->actorLpaToken);

        $this->assertNotEmpty($result);
        $this->assertEquals($result, $this->new_lpa_response);
    }

    #[Test]
    public function it_removes_an_lpa_from_a_user_account_and_cancels_their_active_codes_only(): void
    {
        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn($this->userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->getCodesByLpaId($this->userActorLpa['SiriusUid'])
            ->willReturn($this->viewerCodes);


        $this->viewerCodesInterfaceProphecy
            ->removeActorAssociation($this->viewerCodes[0]['ViewerCode'], (string)$this->userActorLpa['ActorId'])
            ->willReturn(true)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->cancel($this->viewerCodes[0]['ViewerCode'], Argument::type('Datetime'))
            ->willReturn(true)
            ->shouldNotBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->removeActorAssociation($this->viewerCodes[1]['ViewerCode'], (string)$this->userActorLpa['ActorId'])
            ->willReturn(true)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->cancel($this->viewerCodes[1]['ViewerCode'], Argument::type('Datetime'))
            ->shouldNotBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->removeActorAssociation($this->viewerCodes[2]['ViewerCode'], (string)$this->userActorLpa['ActorId'])
            ->willReturn(true)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->cancel($this->viewerCodes[2]['ViewerCode'], Argument::type('Datetime'))
            ->shouldNotBeCalled();

        $this->lpaManagerProphecy
            ->getByUid($this->userActorLpa['SiriusUid'], $this->userActorLpa['UserId'])
            ->willReturn($this->lpaRemovedData);

        $this->userLpaActorMapInterfaceProphecy
            ->delete($this->actorLpaToken)
            ->willReturn($this->deletedData);

        $result = ($this->deleteLpa())($this->userId, $this->actorLpaToken);

        $this->assertNotEmpty($result);
        $this->assertEquals($result, $this->old_lpa_response);
    }

    #[Test]
    public function it_removes_a_new_lpa_from_a_user_account_and_cancels_their_active_codes_only(): void
    {
        $userActorLpa = [
            'LpaUid' => 'M-789Q-P4DF-4UX3',
            'Added'     => (new DateTime())->modify('-6 months')->format('Y-m-d'),
            'Id'        => $this->actorLpaToken,
            'ActorId'   => '1',
            'UserId'    => $this->userId,
        ];

        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn($userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->getCodesByLpaId($userActorLpa['LpaUid'])
            ->willReturn($this->viewerCodes);


        $this->viewerCodesInterfaceProphecy
            ->removeActorAssociation($this->viewerCodes[0]['ViewerCode'], (string)$userActorLpa['ActorId'])
            ->willReturn(true)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->cancel($this->viewerCodes[0]['ViewerCode'], Argument::type('Datetime'))
            ->willReturn(true)
            ->shouldNotBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->removeActorAssociation($this->viewerCodes[1]['ViewerCode'], (string)$userActorLpa['ActorId'])
            ->willReturn(true)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->cancel($this->viewerCodes[1]['ViewerCode'], Argument::type('Datetime'))
            ->shouldNotBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->removeActorAssociation($this->viewerCodes[2]['ViewerCode'], (string)$userActorLpa['ActorId'])
            ->willReturn(true)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->cancel($this->viewerCodes[2]['ViewerCode'], Argument::type('Datetime'))
            ->shouldNotBeCalled();

        $this->lpaManagerProphecy
            ->getByUid($userActorLpa['LpaUid'], $userActorLpa['UserId'])
            ->willReturn($this->combinedLpaRemovedData);

        $this->userLpaActorMapInterfaceProphecy
            ->delete($this->actorLpaToken)
            ->willReturn($this->deletedData);

        $result = ($this->deleteLpa())($this->userId, $this->actorLpaToken);

        $this->assertNotEmpty($result);
        $this->assertEquals($result, $this->new_lpa_response);
    }

    #[Test]
    public function it_throws_exception_if_actor_lpa_token_not_found(): void
    {
        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn(null)
            ->shouldBeCalled();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('User actor lpa record not found for actor token - ' . $this->actorLpaToken);

        ($this->deleteLpa())($this->userId, $this->actorLpaToken);
    }

    #[Test]
    public function it_throws_exception_if_user_id_does_not_match_actor_lpa_data(): void
    {
        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn($this->userActorLpa)
            ->shouldBeCalled();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage(
            'User Id passed does not match the user in userActorLpaMap for token - ' .
            $this->actorLpaToken
        );
        ($this->deleteLpa())('wR0ng1D', $this->actorLpaToken);
    }

    #[Test]
    public function it_throws_an_error_if_deleted_data_does_not_match_row_data(): void
    {
        $this->userLpaActorMapInterfaceProphecy
            ->get($this->actorLpaToken)
            ->willReturn($this->userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy
            ->getCodesByLpaId($this->userActorLpa['SiriusUid'])
            ->willReturn([]);

        $this->lpaManagerProphecy
            ->getByUid($this->userActorLpa['SiriusUid'], $this->userActorLpa['UserId'])
            ->willReturn($this->lpaRemovedData);

        $this->deletedData['Id'] = 'd1ffer3nt-Id-1234';

        $this->userLpaActorMapInterfaceProphecy
            ->delete($this->actorLpaToken)
            ->willReturn($this->deletedData);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->expectExceptionMessage('Incorrect LPA data deleted from users account');

        ($this->deleteLpa())($this->userId, $this->actorLpaToken);
    }

    private function deleteLpa(): RemoveLpa
    {
        return new RemoveLpa(
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->lpaManagerProphecy->reveal(),
            $this->viewerCodesInterfaceProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );
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
                                                           firstname:    'Donor',
                                                           id:           '123456789',
                                                           linked:       [],
                                                           middlenames:  null,
                                                           otherNames:   null,
                                                           postcode:     'DN37 5SH',
                                                           surname:      'Person',
                                                           systemStatus: null,
                                                           town:         '',
                                                           uId:          '700000055554',
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
            uId:                                       'M-789Q-P4DF-4UX3',
            whenTheLpaCanBeUsed:                       null,
            withdrawnDate:                             null
        );
    }

    private function getLpaDataFixtureOld(): SiriusLpa
    {
        return
            new SiriusLpa(
                [
                    'uId' => '700000055554',
                    'caseSubtype' => 'hw',
                    'donor' => $this->donorFixtureOld(),
                    'attorneys' => [],
                ],
                $this->loggerProphecy->reveal(),
            );
    }

    private function donorFixtureOld(): SiriusPerson
    {
        return new SiriusPerson(
            [
                'uId'       => '700000055554',
                'dob'       => '1975-10-05',
                'firstname' => 'Donor',
                'surname'   => 'Person',
                'addresses' => [
                    [
                        'postcode' => 'PY1 3Kd',
                    ],
                ],
            ],
            $this->loggerProphecy->reveal(),
        );
    }
}
