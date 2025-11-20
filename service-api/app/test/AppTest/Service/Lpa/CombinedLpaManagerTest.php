<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\DynamoDb\ViewerCodes;
use App\DataAccess\Repository\{InstructionsAndPreferencesImagesInterface,
    Response\InstructionsAndPreferencesImages,
    Response\Lpa,
    UserLpaActorMapInterface,
    ViewerCodeActivityInterface,
    ViewerCodesInterface};
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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class CombinedLpaManagerTest extends TestCase
{
    use ProphecyTrait;

    private DataStoreLpas|ObjectProphecy $dataStoreLpasProphecy;
    private FilterActiveActors|ObjectProphecy $filterActiveActorsProphecy;
    private ObjectProphecy|InstructionsAndPreferencesImagesInterface $instructionsAndPreferencesImagesProphecy;
    private IsValidLpa|ObjectProphecy $isValidLpaProphecy;
    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private RejectInvalidLpa|ObjectProphecy $rejectInvalidLpaProphecy;
    private ResolveActor|ObjectProphecy $resolveActorProphecy;
    private ResolveLpaTypes|ObjectProphecy $resolveLpaTypesProphecy;
    private SiriusLpas|ObjectProphecy $siriusLpasProphecy;
    private UserLpaActorMapInterface|ObjectProphecy $userLpaActorMapInterfaceProphecy;
    private ObjectProphecy|ViewerCodes $viewerCodesActivityProphecy;
    private ObjectProphecy|ViewerCodes $viewerCodesProphecy;

    /**
     * @throws InterfaceNotFoundException
     * @throws DoubleException
     */
    public function setUp(): void
    {
        $this->userLpaActorMapInterfaceProphecy         = $this->prophesize(UserLpaActorMapInterface::class);
        $this->siriusLpasProphecy                       = $this->prophesize(SiriusLpas::class);
        $this->dataStoreLpasProphecy                    = $this->prophesize(DataStoreLpas::class);
        $this->viewerCodesProphecy                      = $this->prophesize(ViewerCodesInterface::class);
        $this->viewerCodesActivityProphecy              = $this->prophesize(ViewerCodeActivityInterface::class);
        $this->instructionsAndPreferencesImagesProphecy
            = $this->prophesize(InstructionsAndPreferencesImagesInterface::class);
        $this->resolveLpaTypesProphecy                  = $this->prophesize(ResolveLpaTypes::class);
        $this->resolveActorProphecy                     = $this->prophesize(ResolveActor::class);
        $this->isValidLpaProphecy                       = $this->prophesize(IsValidLpa::class);
        $this->filterActiveActorsProphecy               = $this->prophesize(FilterActiveActors::class);
        $this->rejectInvalidLpaProphecy                 = $this->prophesize(RejectInvalidLpa::class);
        $this->loggerProphecy                           = $this->prophesize(LoggerInterface::class);
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

        $this->userLpaActorMapInterfaceProphecy->getByUserId($testUserId)->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesProphecy
            ->__invoke([$userLpaActorMapResponse[0]])
            ->willReturn(
                [
                    [],
                    [$dataStoreLpaResponse->getData()->uId],
                ]
            );
        $this->dataStoreLpasProphecy
            ->setOriginatorId($testUserId)
            ->shouldBeCalled()
            ->willReturn($this->dataStoreLpasProphecy->reveal());
        $this->dataStoreLpasProphecy
            ->lookup([$dataStoreLpaResponse->getData()->uId ?? ''])
            ->willReturn([$dataStoreLpaResponse]);
        $this->resolveActorProphecy
            ->__invoke(
                $dataStoreLpaResponse->getData(),
                $userLpaActorMapResponse[0]['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $dataStoreLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaProphecy->__invoke($dataStoreLpaResponse->getData())->willReturn(true);

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

        $this->userLpaActorMapInterfaceProphecy->getByUserId($testUserId)->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesProphecy
            ->__invoke($userLpaActorMapResponse)
            ->willReturn(
                [
                    [$siriusLpaResponse->getData()->uId],
                    [$dataStoreLpaResponse->getData()->uId],
                ]
            );
        $this->siriusLpasProphecy
            ->lookup([$siriusLpaResponse->getData()->uId])
            ->willReturn(
                [
                    $siriusLpaResponse->getData()->uId ?? '' => $siriusLpaResponse,
                ],
            );
        $this->dataStoreLpasProphecy
            ->setOriginatorId($testUserId)
            ->shouldBeCalled()
            ->willReturn($this->dataStoreLpasProphecy->reveal());
        $this->dataStoreLpasProphecy
            ->lookup([$dataStoreLpaResponse->getData()->uId ?? ''])
            ->willReturn([$dataStoreLpaResponse]);
        $this->resolveActorProphecy
            ->__invoke(
                $siriusLpaResponse->getData(),
                $userLpaActorMapResponse[0]['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $siriusLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->resolveActorProphecy
            ->__invoke(
                $dataStoreLpaResponse->getData(),
                $userLpaActorMapResponse[1]['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $dataStoreLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaProphecy->__invoke($siriusLpaResponse->getData())->willReturn(true);
        $this->isValidLpaProphecy->__invoke($dataStoreLpaResponse->getData())->willReturn(true);

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

        $this->userLpaActorMapInterfaceProphecy->getByUserId($testUserId)->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesProphecy
            ->__invoke([$userLpaActorMapResponse[0]])
            ->willReturn(
                [
                    ['700000000047'],
                    [],
                ]
            );
        $this->siriusLpasProphecy
            ->lookup(['700000000047'])
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
                        ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                        ['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false], // not active
                        ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                        ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                        ['id' => 5, 'systemStatus' => true], // ghost
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
                    ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                    ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                    ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
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

        $this->siriusLpasProphecy->get($testUid)->willReturn($lpaResponse);
        $this->dataStoreLpasProphecy->get(Argument::any())->shouldNotBeCalled();
        $this->filterActiveActorsProphecy->__invoke($lpaResponse->getData())->willReturn($filteredLpa);

        $service = $this->getLpaService();
        $result  = $service->getByUid($testUid);

        $this->assertEquals($filteredLpa, $result->getData());
    }

    #[Test]
    public function can_get_by_lpastore_uid()
    {
        $testUid    = new LpaUid('M-7890-0400-4003');
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

        $this->dataStoreLpasProphecy
            ->setOriginatorId($testUserId)
            ->shouldBeCalled()
            ->willReturn($this->dataStoreLpasProphecy->reveal());
        $this->dataStoreLpasProphecy->get($testUid)->willReturn($lpaResponse);
        $this->siriusLpasProphecy->get(Argument::any())->shouldNotBeCalled();
        $this->filterActiveActorsProphecy->__invoke($lpaResponse->getData())->willReturn($filteredLpa);

        $service = $this->getLpaService();
        $result  = $service->getByUid($testUid, $testUserId);

        $this->assertEquals($filteredLpa, $result->getData());
    }

    #[Test]
    public function get_by_sirius_uid_returns_null_when_no_lpa_data()
    {
        $testUid = new LpaUid('700000000047');

        $this->siriusLpasProphecy->get($testUid)->willReturn(null);

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
            'Added'      => new DateTimeImmutable('now'),
        ];

        $this->userLpaActorMapInterfaceProphecy->get($testLpaToken)->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesProphecy
            ->__invoke([$userLpaActorMapResponse])
            ->willReturn(
                [
                    [$siriusLpaResponse->getData()->uId],
                    [],
                ]
            );
        $this->siriusLpasProphecy
            ->get($siriusLpaResponse->getData()->uId ?? '')
            ->willReturn($siriusLpaResponse);
        $this->filterActiveActorsProphecy
            ->__invoke($siriusLpaResponse->getData())
            ->willReturn($siriusLpaResponse->getData());
        $this->resolveActorProphecy
            ->__invoke(
                $siriusLpaResponse->getData(),
                $userLpaActorMapResponse['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $siriusLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaProphecy->__invoke($siriusLpaResponse->getData())->willReturn(true);

        $service = $this->getLpaService();
        $result  = $service->getByUserLpaActorToken($testLpaToken, $testUserId);

        $this->assertEquals($siriusLpaResponse->getData(), $result['lpa']);
        $this->assertEquals($siriusLpaResponse->getLookupTime()->format(DateTimeInterface::ATOM), $result['date']);
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
            'Id'         => $testLpaToken,
            'UserId'     => $testUserId,
            'SiriusUid'  => $dataStoreLpaResponse->getData()->uId,
            'ActorId'    => $dataStoreLpaResponse->getData()->attorneys[0]->uId,
            'ActivateBy' => (new DateTimeImmutable('now'))->add(new DateInterval('P1Y'))->getTimeStamp(),
            'Added'      => new DateTimeImmutable('now'),
        ];

        $this->userLpaActorMapInterfaceProphecy->get($testLpaToken)->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesProphecy
            ->__invoke([$userLpaActorMapResponse])
            ->willReturn(
                [
                    [],
                    [$dataStoreLpaResponse->getData()->uId],
                ]
            );
        $this->dataStoreLpasProphecy
            ->setOriginatorId($testUserId)
            ->shouldBeCalled()
            ->willReturn($this->dataStoreLpasProphecy->reveal());
        $this->dataStoreLpasProphecy
            ->get($dataStoreLpaResponse->getData()->uId ?? '')
            ->willReturn($dataStoreLpaResponse);
        $this->filterActiveActorsProphecy
            ->__invoke($dataStoreLpaResponse->getData())
            ->willReturn($dataStoreLpaResponse->getData());
        $this->resolveActorProphecy
            ->__invoke(
                $dataStoreLpaResponse->getData(),
                $userLpaActorMapResponse['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $dataStoreLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaProphecy->__invoke($dataStoreLpaResponse->getData())->willReturn(true);

        $service = $this->getLpaService();
        $result  = $service->getByUserLpaActorToken($testLpaToken, $testUserId);

        $this->assertEquals($dataStoreLpaResponse->getData(), $result['lpa']);
        $this->assertEquals($dataStoreLpaResponse->getLookupTime()->format(DateTimeInterface::ATOM), $result['date']);
    }

    #[Test]
    public function get_by_user_lpa_actor_token_sirius_returns_null_when_user_not_match()
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

        $this->userLpaActorMapInterfaceProphecy->get($testLpaToken)->willReturn($userLpaActorMapResponse);

        $service = $this->getLpaService();
        $result  = $service->getByUserLpaActorToken($testLpaToken, 'userId-2');

        $this->assertNull($result);
    }

    #[Test]
    public function get_by_user_lpa_actor_token_datastore_returns_null_when_lpa_data_missing()
    {
        $testLpaToken = 'token-1';
        $testUserId   = 'userId-1';

        $userLpaActorMapResponse = [
            'Id'      => $testLpaToken,
            'UserId'  => $testUserId,
            'LpaUid'  => 'M-7890-0400-40034',
            'ActorId' => '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
            'Added'   => new DateTimeImmutable('now'),
        ];

        $this->userLpaActorMapInterfaceProphecy->get($testLpaToken)->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesProphecy
            ->__invoke([$userLpaActorMapResponse])
            ->willReturn(
                [
                    [],
                    ['M-7890-0400-40034'],
                ]
            );
        $this->dataStoreLpasProphecy
            ->setOriginatorId($testUserId)
            ->shouldBeCalled()
            ->willReturn($this->dataStoreLpasProphecy->reveal());
        $this->dataStoreLpasProphecy
            ->get('M-7890-0400-40034')
            ->willReturn(null);

        $service = $this->getLpaService();
        $result  = $service->getByUserLpaActorToken($testLpaToken, 'userId-1');

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
        $this->viewerCodesProphecy
            ->get('code')
            ->willReturn(
                [
                    'ViewerCode'   => 'code',
                    'SiriusUid'    => '700000000000',
                    'Expires'      => new DateTimeImmutable('+1 hour'),
                    'Organisation' => 'bank',
                ]
            );

        $this->siriusLpasProphecy
            ->get('700000000000')
            ->shouldBeCalled()
            ->willReturn(null);

        $service = $this->getLpaService();

        $this->expectException(NotFoundException::class);
        $service->getByViewerCode('code', 'surname', 'organisation');
    }

    #[Test]
    public function cannot_get_lpastore_by_viewer_code_when_lpa_no_longer_available()
    {
        $testCode = 'code';

        $this->viewerCodesProphecy
            ->get('code')
            ->willReturn(
                [
                    'ViewerCode'   => 'code',
                    'LpaUid'       => 'M-XXXX-XXXX-XXXX',
                    'Expires'      => new DateTimeImmutable('+1 hour'),
                    'Organisation' => 'bank',
                ]
            );
        $this->dataStoreLpasProphecy
            ->setOriginatorId('V-' . $testCode)
            ->shouldBeCalled()
            ->willReturn($this->dataStoreLpasProphecy->reveal());
        $this->dataStoreLpasProphecy
            ->get('M-XXXX-XXXX-XXXX')
            ->shouldBeCalled()
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

        $this->viewerCodesProphecy
            ->get('code')
            ->willReturn(
                [
                    'ViewerCode' => 'code',
                    'SiriusUid'  => $siriusLpaResponse->getData()->uId,
                    //'Expires'    => new DateTimeImmutable('+1 hour'), <- Expires is removed
                    'Organisation' => 'bank',
                ]
            );
        $this->siriusLpasProphecy
            ->get($siriusLpaResponse->getData()->uId)
            ->shouldBeCalled()
            ->willReturn($siriusLpaResponse);
        $this->filterActiveActorsProphecy
            ->__invoke($siriusLpaResponse->getData())
            ->willReturn($siriusLpaResponse->getData());
        $this->rejectInvalidLpaProphecy
            ->__invoke(
                $siriusLpaResponse,
                'code',
                $siriusLpaResponse->getData()->getDonor()->getSurname(),
                Argument::type('array'),
            )
            ->willThrow(new MissingCodeExpiryException());

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

        $this->viewerCodesProphecy
            ->get('code')
            ->willReturn(
                [
                    'ViewerCode'   => 'code',
                    'SiriusUid'    => $siriusLpaResponse->getData()->uId,
                    'Expires'      => new DateTimeImmutable('+1 hour'),
                    'Organisation' => 'bank',
                ]
            );
        $this->siriusLpasProphecy
            ->get($siriusLpaResponse->getData()->uId)
            ->shouldBeCalled()
            ->willReturn($siriusLpaResponse);
        $this->filterActiveActorsProphecy
            ->__invoke($siriusLpaResponse->getData())
            ->willReturn($siriusLpaResponse->getData());
        $this->instructionsAndPreferencesImagesProphecy
            ->getInstructionsAndPreferencesImages((int) $siriusLpaResponse->getData()->uId)
            ->shouldBeCalled()
            ->willReturn($this->prophesize(InstructionsAndPreferencesImages::class)->reveal());

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

        $this->viewerCodesProphecy
            ->get('code')
            ->willReturn(
                [
                    'ViewerCode'   => $testCode,
                    'LpaUid'       => $lpaStoreResponse->getData()->uId,
                    'Expires'      => new DateTimeImmutable('+1 hour'),
                    'Organisation' => 'bank',
                ]
            );
        $this->dataStoreLpasProphecy
            ->setOriginatorId('V-' . $testCode)
            ->shouldBeCalled()
            ->willReturn($this->dataStoreLpasProphecy->reveal());
        $this->dataStoreLpasProphecy
            ->get($lpaStoreResponse->getData()->uId)
            ->shouldBeCalled()
            ->willReturn($lpaStoreResponse);
        $this->filterActiveActorsProphecy
            ->__invoke($lpaStoreResponse->getData())
            ->willReturn($lpaStoreResponse->getData());
        $this->viewerCodesActivityProphecy
            ->recordSuccessfulLookupActivity($testCode, 'organisation')
            ->shouldBeCalled();

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

        $this->viewerCodesProphecy
            ->get('code')
            ->willReturn(
                [
                    'ViewerCode'   => $testCode,
                    'LpaUid'       => $lpaStoreResponse->getData()->uId,
                    'Expires'      => new DateTimeImmutable('+1 hour'),
                    'Organisation' => 'bank',
                ]
            );
        $this->dataStoreLpasProphecy
            ->setOriginatorId('V-' . $testCode)
            ->shouldBeCalled()
            ->willReturn($this->dataStoreLpasProphecy->reveal());
        $this->dataStoreLpasProphecy
            ->get($lpaStoreResponse->getData()->uId)
            ->shouldBeCalled()
            ->willReturn($lpaStoreResponse);
        $this->filterActiveActorsProphecy
            ->__invoke($lpaStoreResponse->getData())
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
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->siriusLpasProphecy->reveal(),
            $this->dataStoreLpasProphecy->reveal(),
            $this->viewerCodesProphecy->reveal(),
            $this->viewerCodesActivityProphecy->reveal(),
            $this->instructionsAndPreferencesImagesProphecy->reveal(),
            $this->resolveLpaTypesProphecy->reveal(),
            $this->resolveActorProphecy->reveal(),
            $this->isValidLpaProphecy->reveal(),
            $this->filterActiveActorsProphecy->reveal(),
            $this->rejectInvalidLpaProphecy->reveal(),
            $this->loggerProphecy->reveal(),
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
        $lpaData = json_decode(file_get_contents(__DIR__ . '/../../../fixtures/4UX3.json'), true);
        $lpaData = array_merge($lpaData, $overwrite);

        /** @var LpaStore */
        return (new LpaDataFormatter())->hydrateObject($lpaData);
    }
}
