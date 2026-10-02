<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\{InstructionsAndPreferencesImagesInterface,
    Response\InstructionsAndPreferencesImages,
    Response\Lpa,
    UserLpaActorMapInterface,
    ViewerCodeActivityInterface,
    ViewerCodesInterface};
use App\DataAccess\Repository\AuditableLpasInterface;
use App\DataAccess\Repository\LpasInterface;
use App\Entity\LpaStore\LpaStore;
use App\Entity\Sirius\SiriusLpa;
use App\Exception\{ApiException, MissingCodeExpiryException, NotFoundException};
use App\Service\Lpa\{Combined\FilterActiveActors,
    Combined\RejectInvalidLpa,
    Combined\ResolveLpaTypes,
    CombinedLpaManager,
    IsValidLpa,
    LpaDataFormatter,
    ResolveActor};
use App\Value\LpaUid;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CombinedLpaManagerTest extends TestCase
{
    private AuditableLpasInterface&MockObject $dataStoreLpasMock;
    private FilterActiveActors&MockObject $filterActiveActorsMock;
    private InstructionsAndPreferencesImagesInterface&MockObject $instructionsAndPreferencesImagesMock;
    private IsValidLpa&MockObject $isValidLpaMock;
    private LoggerInterface&MockObject $loggerMock;
    private RejectInvalidLpa&MockObject $rejectInvalidLpaMock;
    private ResolveActor&MockObject $resolveActorMock;
    private ResolveLpaTypes&MockObject $resolveLpaTypesMock;
    private LpasInterface&MockObject $siriusLpasMock;
    private UserLpaActorMapInterface&MockObject $userLpaActorMapInterfaceMock;
    private ViewerCodeActivityInterface&MockObject $viewerCodesActivityMock;
    private ViewerCodesInterface&MockObject $viewerCodesMock;

    public function setUp(): void
    {
        $this->userLpaActorMapInterfaceMock = $this->createMock(UserLpaActorMapInterface::class);
        $this->siriusLpasMock               = $this->createMock(LpasInterface::class);
        $this->dataStoreLpasMock            = $this->createMock(AuditableLpasInterface::class);
        $this->viewerCodesMock              = $this->createMock(ViewerCodesInterface::class);
        $this->viewerCodesActivityMock      = $this->createMock(ViewerCodeActivityInterface::class);
        $this->instructionsAndPreferencesImagesMock
            = $this->createMock(InstructionsAndPreferencesImagesInterface::class);
        $this->resolveLpaTypesMock    = $this->createMock(ResolveLpaTypes::class);
        $this->resolveActorMock       = $this->createMock(ResolveActor::class);
        $this->isValidLpaMock         = $this->createMock(IsValidLpa::class);
        $this->filterActiveActorsMock = $this->createMock(FilterActiveActors::class);
        $this->rejectInvalidLpaMock   = $this->createMock(RejectInvalidLpa::class);
        $this->loggerMock             = $this->createMock(LoggerInterface::class);
    }

    #[Test]
    public function can_get_all_active_for_user()
    {
        $testUserId = 'test-user-id';

        $siriusLpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $dataStoreLpaResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $userLpaActorMapResponse = [
            [
                'Id'      => 'token-2',
                'LpaUid'  => $dataStoreLpaResponse->getData()->uId,
                'ActorId' => $dataStoreLpaResponse->getData()->attorneys[0]->uId,
                'Added'   => new DateTimeImmutable('now'),
            ],
            [
                'Id'         => 'token-3',
                'SiriusUid'  => $siriusLpaResponse->getData()->uId,
                'ActorId'    => $siriusLpaResponse->getData()->attorneys[0]->uId,
                'ActivateBy' => (new DateTimeImmutable('now'))->add(new DateInterval('P1Y'))->getTimeStamp(),
                'Added'      => new DateTimeImmutable('now'),
            ],
        ];

        $this->userLpaActorMapInterfaceMock
            ->method('getByUserId')
            ->with($testUserId)
            ->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesMock
            ->method('__invoke')
            ->with([$userLpaActorMapResponse[0]])
            ->willReturn(
                [
                    [],
                    [$dataStoreLpaResponse->getData()->uId],
                ]
            );
        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('setOriginatorId')
            ->with($testUserId)
            ->willReturnSelf();
        $this->dataStoreLpasMock
            ->method('lookup')
            ->with([$dataStoreLpaResponse->getData()->uId ?? ''])
            ->willReturn([$dataStoreLpaResponse]);
        $this->resolveActorMock
            ->method('__invoke')
            ->with(
                $dataStoreLpaResponse->getData(),
                $userLpaActorMapResponse[0]['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $dataStoreLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaMock
            ->method('__invoke')
            ->with($dataStoreLpaResponse->getData())
            ->willReturn(true);

        $service = $this->getLpaService();
        $result  = $service->getAllActiveForUser($testUserId);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('token-2', $result);
        $this->assertArrayNotHasKey('token-3', $result);
        $this->assertSame($result['token-2']['user-lpa-actor-token'], 'token-2');
        $this->assertEquals($dataStoreLpaResponse->getData(), $result['token-2']['lpa']);
    }

    #[Test]
    public function can_get_all_for_user()
    {
        $testUserId = 'test-user-id';

        $siriusLpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $dataStoreLpaResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $userLpaActorMapResponse = [
            [
                'Id'        => 'token-1',
                'SiriusUid' => $siriusLpaResponse->getData()->uId,
                'ActorId'   => $siriusLpaResponse->getData()->attorneys[0]->uId,
                'Added'     => new DateTimeImmutable('now'),
            ],
            [
                'Id'      => 'token-2',
                'LpaUid'  => $dataStoreLpaResponse->getData()->uId,
                'ActorId' => $dataStoreLpaResponse->getData()->attorneys[0]->uId,
                'Added'   => new DateTimeImmutable('now'),
            ],
        ];

        $this->userLpaActorMapInterfaceMock
            ->method('getByUserId')
            ->with($testUserId)
            ->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesMock
            ->method('__invoke')
            ->with($userLpaActorMapResponse)
            ->willReturn(
                [
                    [$siriusLpaResponse->getData()->uId],
                    [$dataStoreLpaResponse->getData()->uId],
                ]
            );
        $this->siriusLpasMock
            ->method('lookup')
            ->with([$siriusLpaResponse->getData()->uId])
            ->willReturn(
                [
                    $siriusLpaResponse->getData()->uId ?? '' => $siriusLpaResponse,
                ],
            );
        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('setOriginatorId')
            ->with($testUserId)
            ->willReturnSelf();
        $this->dataStoreLpasMock
            ->method('lookup')
            ->with([$dataStoreLpaResponse->getData()->uId ?? ''])
            ->willReturn([$dataStoreLpaResponse]);
        $this->resolveActorMock
            ->method('__invoke')
            ->willReturnMap(
                [
                    [
                        $siriusLpaResponse->getData(),
                        $userLpaActorMapResponse[0]['ActorId'],
                        new ResolveActor\LpaActor(
                            $siriusLpaResponse->getData()->attorneys[0],
                            ResolveActor\ActorType::ATTORNEY
                        ),
                    ],
                    [
                        $dataStoreLpaResponse->getData(),
                        $userLpaActorMapResponse[1]['ActorId'],
                        new ResolveActor\LpaActor(
                            $dataStoreLpaResponse->getData()->attorneys[0],
                            ResolveActor\ActorType::ATTORNEY
                        ),
                    ],
                ]
            );
        $this->isValidLpaMock
            ->method('__invoke')
            ->willReturnMap(
                [
                    [
                        $siriusLpaResponse->getData(),
                        true,
                    ],
                    [
                        $dataStoreLpaResponse->getData(),
                        true,
                    ],
                ]
            );

        $service = $this->getLpaService();
        $result  = $service->getAllForUser($testUserId);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('token-1', $result);
        $this->assertArrayHasKey('token-2', $result);
        $this->assertSame($result['token-1']['user-lpa-actor-token'], 'token-1');
        $this->assertSame($result['token-2']['user-lpa-actor-token'], 'token-2');
        $this->assertEquals($siriusLpaResponse->getData(), $result['token-1']['lpa']);
        $this->assertEquals($dataStoreLpaResponse->getData(), $result['token-2']['lpa']);
    }

    #[Test]
    public function returns_missing_if_lpa_not_fetched()
    {
        $testUserId = 'test-user-id';

        $userLpaActorMapResponse = [
            [
                'Id'      => 'token-1',
                'LpaUid'  => '700000000047',
                'ActorId' => '700000000518',
                'Added'   => new DateTimeImmutable('now'),
            ],
        ];

        $this->userLpaActorMapInterfaceMock
            ->method('getByUserId')
            ->with($testUserId)
            ->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesMock
            ->method('__invoke')
            ->with([$userLpaActorMapResponse[0]])
            ->willReturn(
                [
                    ['700000000047'],
                    [],
                ]
            );
        $this->siriusLpasMock
            ->method('lookup')
            ->with(['700000000047'])
            ->willReturn([]);

        $service = $this->getLpaService();
        $result  = $service->getAllActiveForUser($testUserId);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('token-1', $result);
        $this->assertSame($result['token-1']['user-lpa-actor-token'], 'token-1');
        $this->assertSame($result['token-1']['error'], 'NO_LPA_FOUND');
    }

    #[Test]
    public function can_get_by_sirius_uid()
    {
        $testUid = new LpaUid('700000000047');

        $lpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(
                overwrite: [
                    'attorneys'         => [
                        [
                            'id'           => 1,
                            'firstname'    => 'A',
                            'surname'      => 'B',
                            'systemStatus' => true,
                        ],
                        [
                            'id'           => 2,
                            'firstname'    => 'A',
                            'surname'      => 'B',
                            'systemStatus' => false,
                        ], // not active
                        [
                            'id'           => 3,
                            'firstname'    => 'A',
                            'systemStatus' => true,
                        ],
                        [
                            'id'           => 4,
                            'surname'      => 'B',
                            'systemStatus' => true,
                        ],
                        [
                            'id'           => 5,
                            'systemStatus' => true,
                        ], // ghost
                    ],
                    'trustCorporations' => [
                        [
                            'id'           => 6,
                            'companyName'  => 'XYZ Ltd',
                            'systemStatus' => true,
                        ],
                    ],
                ],
            ),
            new DateTimeImmutable('now'),
        );

        $filteredLpa = $this->loadTestSiriusLpaFixture(
            overwrite: [
                'attorneys'         => [
                    [
                        'id'           => 1,
                        'firstname'    => 'A',
                        'surname'      => 'B',
                        'systemStatus' => true,
                    ],
                    [
                        'id'           => 3,
                        'firstname'    => 'A',
                        'systemStatus' => true,
                    ],
                    [
                        'id'           => 4,
                        'surname'      => 'B',
                        'systemStatus' => true,
                    ],
                ],
                'trustCorporations' => [
                    [
                        'id'           => 6,
                        'companyName'  => 'XYZ Ltd',
                        'systemStatus' => true,
                    ],
                ],
            ],
        );

        $this->siriusLpasMock
            ->method('get')
            ->with($testUid)
            ->willReturn($lpaResponse);
        $this->dataStoreLpasMock
            ->expects($this->never())
            ->method('get');
        $this->filterActiveActorsMock
            ->method('__invoke')
            ->with($lpaResponse->getData())
            ->willReturn($filteredLpa);

        $service = $this->getLpaService();
        $result  = $service->getByUid($testUid);

        $this->assertEquals($filteredLpa, $result->getData());
    }

    #[Test]
    public function can_get_by_lpastore_uid()
    {
        $testUid    = new LpaUid('M-7890-0400-4000');
        $testUserId = 'test-user-id';

        $lpaResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(
                overwrite: [
                    'attorneys' => [
                        [
                            'uid'        => '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
                            'firstNames' => 'Herman',
                            'lastName'   => 'Seakrest',
                            'status'     => 'active',
                        ],
                        [   // replacement
                            'uid'        => '6bbb8221-eded-4835-a1ba-dacdf5ac139c',
                            'firstNames' => 'Test',
                            'lastName'   => 'Testerson',
                            'status'     => 'replacement',
                        ],
                    ],
                ],
            ),
            new DateTimeImmutable('now'),
        );

        // stripping the replacement should match the default then we need to swap out the attorneys
        // as there will *not* be a replacement in there. This is done because there is no way to update
        // the replacement attorneys in the object manually.
        $filteredLpa = $this->loadTestLpaStoreLpaFixture();
        $filteredLpa = $lpaResponse->getData()->withAttorneys($filteredLpa->attorneys);
        $filteredLpa = $lpaResponse->getData()->withTrustCorporations($filteredLpa->trustCorporations);

        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('setOriginatorId')
            ->with($testUserId)
            ->willReturnSelf();
        $this->dataStoreLpasMock
            ->method('get')
            ->with($testUid)
            ->willReturn($lpaResponse);
        $this->siriusLpasMock
            ->expects($this->never())
            ->method('get');
        $this->filterActiveActorsMock
            ->method('__invoke')
            ->with($lpaResponse->getData())
            ->willReturn($filteredLpa);

        $service = $this->getLpaService();
        $result  = $service->getByUid($testUid, $testUserId);

        $this->assertEquals($filteredLpa, $result->getData());
    }

    #[Test]
    public function get_by_sirius_uid_returns_null_when_no_lpa_data()
    {
        $testUid = new LpaUid('700000000047');

        $this->siriusLpasMock
            ->method('get')
            ->with($testUid)
            ->willReturn(null);

        $service = $this->getLpaService();
        $result  = $service->getByUid($testUid);

        $this->assertNull($result);
    }

    #[Test]
    public function can_get_by_user_lpa_actor_token_sirius()
    {
        $testLpaToken = 'token-1';
        $testUserId   = 'userId-1';

        $siriusLpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $userLpaActorMapResponse = [
            'Id'         => $testLpaToken,
            'UserId'     => $testUserId,
            'SiriusUid'  => $siriusLpaResponse->getData()->uId,
            'ActorId'    => $siriusLpaResponse->getData()->attorneys[0]->uId,
            'ActivateBy' => (new DateTimeImmutable('now'))->add(new DateInterval('P1Y'))->getTimeStamp(),
            'Added'      => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
            'DueBy'      => (new DateTimeImmutable('now +1 month'))->format(DateTimeInterface::ATOM),
        ];

        $this->userLpaActorMapInterfaceMock
            ->method('get')
            ->with($testLpaToken)
            ->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesMock
            ->method('__invoke')
            ->with([$userLpaActorMapResponse])
            ->willReturn(
                [
                    [$siriusLpaResponse->getData()->uId],
                    [],
                ]
            );
        $this->siriusLpasMock
            ->method('get')
            ->with($siriusLpaResponse->getData()->uId ?? '')
            ->willReturn($siriusLpaResponse);
        $this->filterActiveActorsMock
            ->method('__invoke')
            ->with($siriusLpaResponse->getData())
            ->willReturn($siriusLpaResponse->getData());
        $this->resolveActorMock
            ->method('__invoke')
            ->with(
                $siriusLpaResponse->getData(),
                $userLpaActorMapResponse['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $siriusLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaMock
            ->method('__invoke')
            ->with($siriusLpaResponse->getData())
            ->willReturn(true);

        $service = $this->getLpaService();
        $result  = $service->getByUserLpaActorToken($testLpaToken, $testUserId);

        $this->assertEquals($siriusLpaResponse->getData(), $result->lpa);
        $this->assertEquals(
            $siriusLpaResponse->getLookupTime()->format(DateTimeInterface::ATOM),
            $result->lookupDateTime->format(DateTimeInterface::ATOM)
        );
    }

    #[Test]
    public function can_get_by_user_lpa_actor_token_lpastore()
    {
        $testLpaToken = 'token-2';
        $testUserId   = 'userId-1';

        $dataStoreLpaResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $userLpaActorMapResponse = [
            'Id'                       => $testLpaToken,
            'UserId'                   => $testUserId,
            'LpaUid'                   => $dataStoreLpaResponse->getData()->uId,
            'ActorId'                  => $dataStoreLpaResponse->getData()->attorneys[0]->uId,
            'Added'                    => new DateTimeImmutable('now'),
            'HasPaperVerificationCode' => true,
        ];

        $this->userLpaActorMapInterfaceMock
            ->method('get')
            ->with($testLpaToken)
            ->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesMock
            ->method('__invoke')
            ->with([$userLpaActorMapResponse])
            ->willReturn(
                [
                    [],
                    [$dataStoreLpaResponse->getData()->uId],
                ]
            );
        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('setOriginatorId')
            ->with($testUserId)
            ->willReturnSelf();
        $this->dataStoreLpasMock
            ->method('get')
            ->with($dataStoreLpaResponse->getData()->uId ?? '')
            ->willReturn($dataStoreLpaResponse);
        $this->filterActiveActorsMock
            ->method('__invoke')
            ->with($dataStoreLpaResponse->getData())
            ->willReturn($dataStoreLpaResponse->getData());
        $this->resolveActorMock
            ->method('__invoke')
            ->with(
                $dataStoreLpaResponse->getData(),
                $userLpaActorMapResponse['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $dataStoreLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaMock
            ->method('__invoke')
            ->with($dataStoreLpaResponse->getData())
            ->willReturn(true);

        $service = $this->getLpaService();
        $result  = $service->getByUserLpaActorToken($testLpaToken, $testUserId);

        $this->assertEquals($dataStoreLpaResponse->getData(), $result->lpa);
        $this->assertSame(true, $result->hasPaperVerificationCode);
        $this->assertEquals(
            $dataStoreLpaResponse->getLookupTime()->format(DateTimeInterface::ATOM),
            $result->lookupDateTime->format(DateTimeInterface::ATOM)
        );
    }

    /**
     * UML-4260 The recorded actor is not resolvable (may have been removed from LPA)
     */
    #[Test]
    public function can_get_by_user_lpa_actor_token_sirius_when_actor_is_now_unresolvable()
    {
        $testLpaToken = 'token-1';
        $testUserId   = 'userId-1';

        $siriusLpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $userLpaActorMapResponse = [
            'Id'         => $testLpaToken,
            'UserId'     => $testUserId,
            'SiriusUid'  => $siriusLpaResponse->getData()->uId,
            'ActorId'    => $siriusLpaResponse->getData()->attorneys[0]->uId,
            'ActivateBy' => (new DateTimeImmutable('now'))->add(new DateInterval('P1Y'))->getTimeStamp(),
            'Added'      => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
            'DueBy'      => (new DateTimeImmutable('now +1 month'))->format(DateTimeInterface::ATOM),
        ];

        $this->userLpaActorMapInterfaceMock
            ->method('get')
            ->with($testLpaToken)
            ->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesMock
            ->method('__invoke')
            ->with([$userLpaActorMapResponse])
            ->willReturn(
                [
                    [$siriusLpaResponse->getData()->uId],
                    [],
                ]
            );
        $this->siriusLpasMock
            ->method('get')
            ->with($siriusLpaResponse->getData()->uId ?? '')
            ->willReturn($siriusLpaResponse);
        $this->filterActiveActorsMock
            ->method('__invoke')
            ->with($siriusLpaResponse->getData())
            ->willReturn($siriusLpaResponse->getData());
        $this->resolveActorMock
            ->method('__invoke')
            ->with(
                $siriusLpaResponse->getData(),
                $userLpaActorMapResponse['ActorId'],
            )->willReturn(null);
        $this->isValidLpaMock
            ->method('__invoke')
            ->with($siriusLpaResponse->getData())
            ->willReturn(true);

        $service = $this->getLpaService();
        $result  = $service->getByUserLpaActorToken($testLpaToken, $testUserId);

        $this->assertEquals($siriusLpaResponse->getData(), $result->lpa);
        $this->assertEquals(
            $siriusLpaResponse->getLookupTime()->format(DateTimeInterface::ATOM),
            $result->lookupDateTime->format(DateTimeInterface::ATOM)
        );
    }

    #[Test]
    public function get_by_user_lpa_actor_token_sirius_returns_throws_exception_when_user_not_match()
    {
        $testLpaToken = 'token-1';
        $testUserId   = 'userId-1';

        $userLpaActorMapResponse = [
            'Id'        => $testLpaToken,
            'UserId'    => $testUserId,
            'SiriusUid' => '700000000047',
            'ActorId'   => '700000005123',
            'Added'     => new DateTimeImmutable('now'),
        ];

        $this->userLpaActorMapInterfaceMock
            ->method('get')
            ->with($testLpaToken)
            ->willReturn($userLpaActorMapResponse);

        $service = $this->getLpaService();

        $this->expectException(NotFoundException::class);
        $service->getByUserLpaActorToken($testLpaToken, 'userId-2');
    }

    #[Test]
    public function get_by_user_lpa_actor_token_datastore_throws_exception_when_lpa_data_missing()
    {
        $testLpaToken = 'token-1';
        $testUserId   = 'userId-1';

        $userLpaActorMapResponse = [
            'Id'      => $testLpaToken,
            'UserId'  => $testUserId,
            'LpaUid'  => 'M-7890-0400-4000',
            'ActorId' => '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
            'Added'   => new DateTimeImmutable('now'),
        ];

        $this->userLpaActorMapInterfaceMock
            ->method('get')
            ->with($testLpaToken)
            ->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesMock
            ->method('__invoke')
            ->with([$userLpaActorMapResponse])
            ->willReturn(
                [
                    [],
                    ['M-7890-0400-4000'],
                ]
            );
        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('setOriginatorId')
            ->with($testUserId)
            ->willReturnSelf();
        $this->dataStoreLpasMock
            ->method('get')
            ->with('M-7890-0400-4000')
            ->willReturn(null);

        $service = $this->getLpaService();

        $this->expectException(NotFoundException::class);
        $service->getByUserLpaActorToken($testLpaToken, 'userId-1');
    }

    #[Test]
    public function get_by_user_lpa_actor_token_sirius_returns_null_when_lpa_not_valid()
    {
        $testLpaToken = 'token-1';
        $testUserId   = 'userId-1';

        $siriusLpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(
                [
                    'status' => 'notRegistered', // not strictly necessary as the validation service call is mocked
                ]
            ),
            new DateTimeImmutable('now'),
        );

        $userLpaActorMapResponse = [
            'Id'        => $testLpaToken,
            'UserId'    => $testUserId,
            'SiriusUid' => $siriusLpaResponse->getData()->uId,
            'ActorId'   => $siriusLpaResponse->getData()->attorneys[0]->uId,
            'Added'     => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
        ];

        $this->userLpaActorMapInterfaceMock
            ->method('get')
            ->with($testLpaToken)
            ->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesMock
            ->method('__invoke')
            ->with([$userLpaActorMapResponse])
            ->willReturn(
                [
                    [$siriusLpaResponse->getData()->uId],
                    [],
                ]
            );
        $this->siriusLpasMock
            ->method('get')
            ->with($siriusLpaResponse->getData()->uId ?? '')
            ->willReturn($siriusLpaResponse);
        $this->filterActiveActorsMock
            ->method('__invoke')
            ->with($siriusLpaResponse->getData())
            ->willReturn($siriusLpaResponse->getData());
        $this->resolveActorMock
            ->method('__invoke')
            ->with(
                $siriusLpaResponse->getData(),
                $userLpaActorMapResponse['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $siriusLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaMock
            ->method('__invoke')
            ->with($siriusLpaResponse->getData())
            ->willReturn(false);

        $service = $this->getLpaService();
        $result  = $service->getByUserLpaActorToken($testLpaToken, $testUserId);

        $this->assertNull($result);
    }

    #[Test]
    public function cannot_get_by_viewer_code_when_not_in_database()
    {
        $service = $this->getLpaService();

        $this->expectException(NotFoundException::class);
        $service->getByViewerCode('code', 'surname', 'organisation');
    }

    #[Test]
    public function cannot_get_siriuslpa_by_viewer_code_when_lpa_no_longer_available()
    {
        $this->viewerCodesMock
            ->method('get')
            ->with('code')
            ->willReturn(
                [
                    'ViewerCode'   => 'code',
                    'SiriusUid'    => '700000000000',
                    'Expires'      => new DateTimeImmutable('+1 hour'),
                    'Organisation' => 'bank',
                ]
            );

        $this->siriusLpasMock
            ->expects($this->once())
            ->method('get')
            ->with('700000000000')
            ->willReturn(null);

        $service = $this->getLpaService();

        $this->expectException(NotFoundException::class);
        $service->getByViewerCode('code', 'surname', 'organisation');
    }

    #[Test]
    public function cannot_get_lpastore_by_viewer_code_when_lpa_no_longer_available()
    {
        $testCode = 'code';

        $this->viewerCodesMock
            ->method('get')
            ->with('code')
            ->willReturn(
                [
                    'ViewerCode'   => 'code',
                    'LpaUid'       => 'M-XXXX-XXXX-XXXX',
                    'Expires'      => new DateTimeImmutable('+1 hour'),
                    'Organisation' => 'bank',
                ]
            );
        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('setOriginatorId')
            ->with('V-' . $testCode)
            ->willReturnSelf();
        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('get')
            ->with('M-XXXX-XXXX-XXXX')
            ->willReturn(null);

        $service = $this->getLpaService();

        $this->expectException(NotFoundException::class);
        $service->getByViewerCode($testCode, 'surname', 'organisation');
    }

    #[Test]
    public function cannot_get_viewercode_as_expires_missing(): void
    {
        $siriusLpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $this->viewerCodesMock
            ->method('get')
            ->with('code')
            ->willReturn(
                [
                    'ViewerCode'   => 'code',
                    'SiriusUid'    => $siriusLpaResponse->getData()->uId,
                    //'Expires'    => new DateTimeImmutable('+1 hour'), <- Expires is removed
                    'Organisation' => 'bank',
                ]
            );
        $this->siriusLpasMock
            ->expects($this->once())
            ->method('get')
            ->with($siriusLpaResponse->getData()->uId)
            ->willReturn($siriusLpaResponse);
        $this->filterActiveActorsMock
            ->method('__invoke')
            ->with($siriusLpaResponse->getData())
            ->willReturn($siriusLpaResponse->getData());
        $this->rejectInvalidLpaMock
            ->method('__invoke')
            ->with(
                $siriusLpaResponse,
                'code',
                $siriusLpaResponse->getData()->getDonor()->getSurname(),
                $this->isType('array'),
            )
            ->willThrowException(new MissingCodeExpiryException());

        $service = $this->getLpaService();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Missing code expiry data in Dynamo response');
        $service->getByViewerCode(
            'code',
            $siriusLpaResponse->getData()->getDonor()->getSurname(),
            'organisation'
        );
    }

    #[Test]
    public function matched_viewercode_loads_images_if_required(): void
    {
        $siriusLpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(
                [
                    'applicationHasGuidance' => true,
                ]
            ),
            new DateTimeImmutable('now'),
        );

        $this->viewerCodesMock
            ->method('get')
            ->with('code')
            ->willReturn(
                [
                    'ViewerCode'   => 'code',
                    'SiriusUid'    => $siriusLpaResponse->getData()->uId,
                    'Expires'      => new DateTimeImmutable('+1 hour'),
                    'Organisation' => 'bank',
                ]
            );
        $this->siriusLpasMock
            ->expects($this->once())
            ->method('get')
            ->with($siriusLpaResponse->getData()->uId)
            ->willReturn($siriusLpaResponse);
        $this->filterActiveActorsMock
            ->method('__invoke')
            ->with($siriusLpaResponse->getData())
            ->willReturn($siriusLpaResponse->getData());
        $this->instructionsAndPreferencesImagesMock
            ->expects($this->once())
            ->method('getInstructionsAndPreferencesImages')
            ->with((int) $siriusLpaResponse->getData()->uId)
            ->willReturn($this->createStub(InstructionsAndPreferencesImages::class));

        $service = $this->getLpaService();

        $result = $service->getByViewerCode(
            'code',
            $siriusLpaResponse->getData()->getDonor()->getSurname(),
            'organisation'
        );

        $this->assertArrayHasKey('iap', $result);
    }

    #[Test]
    public function matched_viewercode_records_successful_lookup(): void
    {
        $testCode = 'code';

        $lpaStoreResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $this->viewerCodesMock
            ->method('get')
            ->with('code')
            ->willReturn(
                [
                    'ViewerCode'   => $testCode,
                    'LpaUid'       => $lpaStoreResponse->getData()->uId,
                    'Expires'      => new DateTimeImmutable('+1 hour'),
                    'Organisation' => 'bank',
                ]
            );
        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('setOriginatorId')
            ->with('V-' . $testCode)
            ->willReturnSelf();
        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('get')
            ->with($lpaStoreResponse->getData()->uId)
            ->willReturn($lpaStoreResponse);
        $this->filterActiveActorsMock
            ->method('__invoke')
            ->with($lpaStoreResponse->getData())
            ->willReturn($lpaStoreResponse->getData());
        $this->viewerCodesActivityMock
            ->expects($this->once())
            ->method('recordSuccessfulLookupActivity')
            ->with($testCode, 'organisation');

        $service = $this->getLpaService();

        $service->getByViewerCode(
            $testCode,
            $lpaStoreResponse->getData()->getDonor()->getSurname(),
            'organisation'
        );
    }

    #[Test]
    public function matched_viewercode_returns_viewercode_record(): void
    {
        $testCode = 'code';

        $lpaStoreResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $this->viewerCodesMock
            ->method('get')
            ->with('code')
            ->willReturn(
                [
                    'ViewerCode'   => $testCode,
                    'LpaUid'       => $lpaStoreResponse->getData()->uId,
                    'Expires'      => new DateTimeImmutable('+1 hour'),
                    'Organisation' => 'bank',
                ]
            );
        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('setOriginatorId')
            ->with('V-' . $testCode)
            ->willReturnSelf();
        $this->dataStoreLpasMock
            ->expects($this->once())
            ->method('get')
            ->with($lpaStoreResponse->getData()->uId)
            ->willReturn($lpaStoreResponse);
        $this->filterActiveActorsMock
            ->method('__invoke')
            ->with($lpaStoreResponse->getData())
            ->willReturn($lpaStoreResponse->getData());

        $service = $this->getLpaService();

        $result = $service->getByViewerCode(
            $testCode,
            $lpaStoreResponse->getData()->getDonor()->getSurname(),
            'organisation'
        );

        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('expires', $result);
        $this->assertArrayHasKey('organisation', $result);
        $this->assertArrayHasKey('lpa', $result);
    }

    private function getLpaService(): CombinedLpaManager
    {
        return new CombinedLpaManager(
            $this->userLpaActorMapInterfaceMock,
            $this->siriusLpasMock,
            $this->dataStoreLpasMock,
            $this->viewerCodesMock,
            $this->viewerCodesActivityMock,
            $this->instructionsAndPreferencesImagesMock,
            $this->resolveLpaTypesMock,
            $this->resolveActorMock,
            $this->isValidLpaMock,
            $this->filterActiveActorsMock,
            $this->rejectInvalidLpaMock,
            $this->loggerMock,
        );
    }

    private function loadTestSiriusLpaFixture(array $overwrite = []): SiriusLpa
    {
        $file    = file_get_contents(__DIR__ . '/../../../fixtures/test_lpa.json');
        $lpaData = json_decode($file, true);
        $lpaData = array_merge($lpaData, $overwrite);

        /** @var SiriusLpa */
        return (new LpaDataFormatter())->hydrateObject($lpaData);
    }

    private function loadTestLpaStoreLpaFixture(array $overwrite = []): LpaStore
    {
        $lpaData = json_decode(file_get_contents(__DIR__ . '/../../../fixtures/4000.json'), true);
        $lpaData = array_merge($lpaData, $overwrite);

        /** @var LpaStore */
        return (new LpaDataFormatter())->hydrateObject($lpaData);
    }
}
