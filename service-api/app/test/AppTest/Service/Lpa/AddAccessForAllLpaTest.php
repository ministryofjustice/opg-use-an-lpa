<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\Response\Lpa;
use App\Exception\BadRequestException;
use App\Exception\LpaActivationKeyAlreadyRequestedException;
use App\Exception\LpaAlreadyAddedException;
use App\Exception\LpaAlreadyHasActivationKeyException;
use App\Exception\LpaDetailsDoNotMatchException;
use App\Exception\NotFoundException;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\AccessForAll\AccessForAllLpaService;
use App\Service\Lpa\AccessForAll\AccessForAllValidation;
use App\Service\Lpa\AccessForAll\AddAccessForAllLpa;
use App\Service\Lpa\AddLpa\LpaAlreadyAdded;
use App\Service\Lpa\FindActorInLpa;
use App\Service\Lpa\FindActorInLpa\ActorMatch;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\Lpa\RestrictSendingLpaForCleansing;
use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\SiriusPerson;
use App\Service\Lpa\ValidateAccessForAllLpaRequirements;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class AddAccessForAllLpaTest extends TestCase
{
    use ProphecyTrait;

    private FindActorInLpa|ObjectProphecy $findActorInLpaProphecy;
    private LpaManagerInterface|ObjectProphecy $lpaManagerProphecy;
    private LpaAlreadyAdded|ObjectProphecy $lpaAlreadyAddedProphecy;
    private AccessForAllLpaService|ObjectProphecy $accessForAllLpaServiceProphecy;
    private ValidateAccessForAllLpaRequirements|ObjectProphecy $validateAccessForAllLpaRequirementsProphecy;
    private RestrictSendingLpaForCleansing|ObjectProphecy $restrictSendingLpaForCleansingProphecy;
    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private FeatureEnabled|ObjectProphecy $featureEnabledProphecy;

    private string $userId;
    private int $lpaUid;

    /** @var array<string, mixed> */
    private array $dataToMatch;

    private ActorMatch $resolvedActor;
    private Lpa $lpa;

    private SiriusLpa $lpaData;

    public function setUp(): void
    {
        $this->findActorInLpaProphecy                      = $this->prophesize(FindActorInLpa::class);
        $this->lpaManagerProphecy                          = $this->prophesize(LpaManagerInterface::class);
        $this->lpaAlreadyAddedProphecy                     = $this->prophesize(LpaAlreadyAdded::class);
        $this->accessForAllLpaServiceProphecy              = $this->prophesize(AccessForAllLpaService::class);
        $this->validateAccessForAllLpaRequirementsProphecy
            = $this->prophesize(ValidateAccessForAllLpaRequirements::class);
        $this->restrictSendingLpaForCleansingProphecy      = $this->prophesize(RestrictSendingLpaForCleansing::class);
        $this->loggerProphecy                              = $this->prophesize(LoggerInterface::class);
        $this->featureEnabledProphecy                      = $this->prophesize(FeatureEnabled::class);

        $this->userId = 'user-zxywq-54321';
        $this->lpaUid = 700000012345;

        $this->lpa     = $this->older_lpa_get_by_uid_response();
        $this->lpaData = $this->lpa->getData();

        $this->dataToMatch = [
            'reference_number'     => $this->lpaUid,
            'dob'                  => '1980-03-01',
            'first_names'          => 'Test Tester', // lpa attorney
            'last_name'            => 'Testing',
            'postcode'             => 'Ab1 2Cd',
            'force_activation_key' => false,
        ];

        $this->resolvedActor = new ActorMatch(
            $this->lpaData->getAttorneys()[1],
            'attorney',
            (string) $this->lpaUid,
        );
    }

    protected function getSut(): AddAccessForAllLpa
    {
        return new AddAccessForAllLpa(
            $this->findActorInLpaProphecy->reveal(),
            $this->lpaManagerProphecy->reveal(),
            $this->lpaAlreadyAddedProphecy->reveal(),
            $this->accessForAllLpaServiceProphecy->reveal(),
            $this->validateAccessForAllLpaRequirementsProphecy->reveal(),
            $this->restrictSendingLpaForCleansingProphecy->reveal(),
            $this->loggerProphecy->reveal(),
        );
    }

    #[Test]
    public function returns_matched_actorId_and_lpaId_when_passing_all_older_lpa_criteria(): void
    {
        $expectedResponse = new AccessForAllValidation(
            $this->resolvedActor,
            $this->lpaData
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->willReturn(null);

        $this->lpaManagerProphecy
            ->getByUid((string) $this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateAccessForAllLpaRequirementsProphecy
            ->__invoke($this->lpaData->toArray());

        $this->findActorInLpaProphecy
            ->__invoke($this->lpa->getData(), $this->dataToMatch)
            ->willReturn($this->resolvedActor);

        $this->accessForAllLpaServiceProphecy
            ->hasActivationCode((string) $this->lpaUid, $this->lpaData->getAttorneys()[1]->getUid())
            ->willReturn(null);

        $result = $this->getSut()->validateRequest($this->userId, $this->dataToMatch);

        $this->assertEquals($expectedResponse, $result);
    }

    #[Test]
    public function older_lpa_lookup_throws_an_exception_if_lpa_already_added(): void
    {
        $alreadyAddedData = [
            'donor'         => [
                'uId'         => '12345',
                'firstname'   => 'Example',
                'middlenames' => 'Donor',
                'surname'     => 'Person',
            ],
            'caseSubtype'   => 'hw',
            'lpaActorToken' => 'qwerty-54321',
        ];

        $expectedException = new LpaAlreadyAddedException($alreadyAddedData);

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->willReturn($alreadyAddedData);

        $this->expectExceptionObject($expectedException);
        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    #[Test]
    public function older_lpa_lookup_throws_an_exception_if_lpa_already_requested(): void
    {
        $createdDate = (new DateTime())->modify('-14 days');

        $activationKeyDueDate = DateTimeImmutable::createFromMutable($createdDate);
        $activationKeyDueDate = $activationKeyDueDate
            ->add(new DateInterval('P10D'))
            ->format('Y-m-d');

        $alreadyAddedData = [
            'donor'                => [
                'uId'         => '12345',
                'firstname'   => 'Example',
                'middlenames' => 'Donor',
                'surname'     => 'Person',
            ],
            'caseSubtype'          => 'hw',
            'lpaActorToken'        => 'qwerty-54321',
            'activationKeyDueDate' => null,
            'notActivated'         => true,
        ];

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->willReturn($alreadyAddedData);

        $this->lpaManagerProphecy
            ->getByUid((string) $this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateAccessForAllLpaRequirementsProphecy
            ->__invoke($this->lpaData->toArray());

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn($this->resolvedActor);

        $this->accessForAllLpaServiceProphecy
            ->hasActivationCode((string) $this->lpaUid, $this->lpaData['attorneys'][1]['uId'])
            ->willReturn($createdDate);

        $expectedException = new LpaActivationKeyAlreadyRequestedException(
            [
                'donor'                => $this->lpaData->getDonor(),
                'caseSubtype'          => $this->lpaData['caseSubtype'],
                'activationKeyDueDate' => $activationKeyDueDate,
            ]
        );

        $this->expectExceptionObject($expectedException);
        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    #[Test]
    public function older_lpa_lookup_successful_if_lpa_already_requested_but_force_flag_true(): void
    {
        $this->dataToMatch['force_activation_key'] = true;

        $alreadyAddedData = [
            'donor'         => [
                'uId'         => '12345',
                'firstname'   => 'Example',
                'middlenames' => 'Donor',
                'surname'     => 'Person',
            ],
            'caseSubtype'   => 'hw',
            'lpaActorToken' => 'qwerty-54321',
            'notActivated'  => true,
        ];

        $actorMatch = new ActorMatch(
            new SiriusPerson(
                [
                    'uId'         => $this->lpaData->getDonor()->getUId(),
                    'firstname'   => 'Donor',
                    'middlenames' => 'Example',
                    'surname'     => 'Person',
                ],
                $this->loggerProphecy->reveal(),
            ),
            'donor',
            (string) $this->lpaUid,
        );

        $expectedResponse = new AccessForAllValidation(
            $actorMatch,
            $this->lpaData,
            'qwerty-54321'
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->willReturn($alreadyAddedData);

        $this->lpaManagerProphecy
            ->getByUid((string) $this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateAccessForAllLpaRequirementsProphecy
            ->__invoke($this->lpaData->toArray());

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn($actorMatch);

        $response = $this->getSut()->validateRequest($this->userId, $this->dataToMatch);

        $this->assertEquals($expectedResponse, $response);
    }

    #[Test]
    public function older_lpa_lookup_throws_an_exception_if_lpa_not_found(): void
    {
        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->lpaManagerProphecy
            ->getByUid((string) $this->lpaUid)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('LPA not found');

        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    #[Test]
    public function older_lpa_lookup_throws_an_exception_if_lpa_registration_date_not_valid(): void
    {
        $invalidLpa = new Lpa(
            new SiriusLpa(
                [
                    'uId'              => $this->lpaUid,
                    'registrationDate' => '2019-08-31',
                    'status'           => 'Registered',
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime()
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->lpaManagerProphecy
            ->getByUid((string) $this->lpaUid)
            ->willReturn($invalidLpa);

        $this->validateAccessForAllLpaRequirementsProphecy
            ->__invoke($invalidLpa->getData()->toArray())
            ->willThrow(new BadRequestException('LPA not eligible due to registration date'));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA not eligible due to registration date');

        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    #[Test]
    public function older_lpa_lookup_throws_an_exception_if_lpa_status_not_registered(): void
    {
        $invalidLpa = new Lpa(
            new SiriusLpa(
                [
                    'uId'              => $this->lpaUid,
                    'registrationDate' => '2019-08-31',
                    'status'           => 'Registered',
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime()
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->lpaManagerProphecy
            ->getByUid((string) $this->lpaUid)
            ->willReturn($invalidLpa);

        $this->validateAccessForAllLpaRequirementsProphecy
            ->__invoke($invalidLpa->getData()->toArray())
            ->willThrow(new NotFoundException('LPA status invalid'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('LPA status invalid');

        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    #[Test]
    public function older_lpa_lookup_throws_an_exception_if_user_data_doesnt_match_lpa(): void
    {
        $dataToMatch = [
            'reference_number'     => $this->lpaUid,
            'dob'                  => '1980-03-01',
            'first_names'          => 'Wrong Name',
            'last_name'            => 'Incorrect',
            'postcode'             => 'wR0 nG1',
            'force_activation_key' => false,
        ];

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->lpaManagerProphecy
            ->getByUid((string) $this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateAccessForAllLpaRequirementsProphecy
            ->__invoke($this->lpaData->toArray());

        $this->expectException(LpaDetailsDoNotMatchException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA details do not match');

        $this->getSut()->validateRequest($this->userId, $dataToMatch);
    }

    #[Test]
    public function older_lpa_lookup_throws_exception_if_lpa_already_has_activation_key(): void
    {
        $createdDate = (new DateTime())->modify('-14 days');

        $activationKeyDueDate = DateTimeImmutable::createFromMutable($createdDate);
        $activationKeyDueDate = $activationKeyDueDate
            ->add(new DateInterval('P10D'))
            ->format('Y-m-d');

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->willReturn(null);

        $this->lpaManagerProphecy
            ->getByUid((string) $this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateAccessForAllLpaRequirementsProphecy
            ->__invoke($this->lpaData->toArray());

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn($this->resolvedActor);

        $this->accessForAllLpaServiceProphecy
            ->hasActivationCode((string) $this->lpaUid, $this->lpaData['attorneys'][1]['uId'])
            ->willReturn($createdDate);

        $expectedException = new LpaAlreadyHasActivationKeyException(
            [
                'donor'                => $this->lpaData->getDonor(),
                'caseSubtype'          => $this->lpaData['caseSubtype'],
                'activationKeyDueDate' => $activationKeyDueDate,
            ]
        );

        $this->expectExceptionObject($expectedException);
        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }

    #[Test]
    public function older_lpa_lookup_throws_exception_if_lpa_already_has_activation_key_but_force_flag_true(): void
    {
        $this->dataToMatch['force_activation_key'] = true;

        $expectedResponse = new AccessForAllValidation(
            $this->resolvedActor,
            $this->lpaData
        );

        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->willReturn(null);

        $this->lpaManagerProphecy
            ->getByUid((string) $this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateAccessForAllLpaRequirementsProphecy
            ->__invoke($this->lpaData->toArray());

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn($this->resolvedActor);

        $result = $this->getSut()->validateRequest($this->userId, $this->dataToMatch);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Returns the lpa data needed for checking in the older LPA journey
     *
     * @return Lpa
     */
    public function older_lpa_get_by_uid_response(): Lpa
    {
        $attorney1 = [
            'uId'          => '700000002222',
            'dob'          => '1977-11-21',
            'firstname'    => 'Attorneyone',
            'middlenames'  => 'Example',
            'surname'      => 'Person',
            'addresses'    => [
                [
                    'postcode' => 'Gg1 2ff',
                ],
            ],
            'systemStatus' => false,
        ];

        $attorney2 = [
            'uId'          => '700000055554',
            'dob'          => '1980-03-01',
            'firstname'    => 'Test',
            'middlenames'  => 'Example',
            'surname'      => 'Testing',
            'addresses'    => [
                [
                    'postcode' => 'Ab1 2Cd',
                ],
            ],
            'systemStatus' => true,
        ];

        return new Lpa(
            new SiriusLpa(
                [
                    'uId'              => $this->lpaUid,
                    'registrationDate' => '2016-01-01',
                    'status'           => 'Registered',
                    'lpaIsCleansed'    => false,
                    'caseSubtype'      => 'pfa',
                    'donor'            => [
                        'uId'         => '700000001111',
                        'dob'         => '1975-10-05',
                        'firstname'   => 'Donor',
                        'middlenames' => 'Example',
                        'surname'     => 'Person',
                        'addresses'   => [
                            [
                                'postcode' => 'PY1 3Kd',
                            ],
                        ],
                    ],
                    'attorneys'        => [
                        $attorney1,
                        $attorney2,
                    ],
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime()
        );
    }

    #[Test]
    public function older_lpa_lookup_throws_not_found_exception_lpa_registered_after_2019_and_restrict_flag_true(): void
    {
        $this->lpaAlreadyAddedProphecy
            ->__invoke($this->userId, (string) $this->lpaUid)
            ->willReturn(null);

        $this->lpaManagerProphecy
            ->getByUid((string) $this->lpaUid)
            ->willReturn($this->lpa);

        $this->validateAccessForAllLpaRequirementsProphecy
            ->__invoke($this->lpaData->toArray());

        $this->findActorInLpaProphecy
            ->__invoke($this->lpaData, $this->dataToMatch)
            ->willReturn($this->resolvedActor);

        $this->restrictSendingLpaForCleansingProphecy
            ->__invoke($this->lpaData->toArray(), $this->resolvedActor)
            ->willThrow(new NotFoundException('LPA not found'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('LPA not found');

        $this->getSut()->validateRequest($this->userId, $this->dataToMatch);
    }
}
