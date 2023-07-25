<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\ActorCode;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\AccessForAllLpaService;
use App\Service\Lpa\ResolveActor;
use DateInterval;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class AccessForAllLpaServiceTest extends TestCase
{
    use ProphecyTrait;

    private FeatureEnabled|ObjectProphecy $featureEnabledProphecy;
    private LpasInterface|ObjectProphecy $lpasInterfaceProphecy;
    private LoggerInterface|ObjectProphecy $loggerProphecy;
    public ActorCodes|ObjectProphecy $actorCodesProphecy;
    private ResolveActor|ObjectProphecy $resolveActorProphecy;
    private UserLpaActorMapInterface|ObjectProphecy $userLpaActorMapProphecy;

    public string $userId;
    public string $lpaUid;
    public string $actorUid;
    public string $additionalInfo;
    public string $lpaActorToken;

    /** @var array<string, mixed> */
    public array $dataToMatch;
    private DateInterval $oneYearInterval;
    private DateInterval $sixWeekInterval;
    private DateInterval $twoWeekInterval;

    public function setUp(): void
    {
        $this->lpasInterfaceProphecy   = $this->prophesize(LpasInterface::class);
        $this->loggerProphecy          = $this->prophesize(LoggerInterface::class);
        $this->actorCodesProphecy      = $this->prophesize(ActorCodes::class);
        $this->userLpaActorMapProphecy = $this->prophesize(UserLpaActorMap::class);
        $this->featureEnabledProphecy  = $this->prophesize(FeatureEnabled::class);
        $this->resolveActorProphecy    = $this->prophesize(ResolveActor::class);

        $this->userId         = 'user-zxywq-54321';
        $this->lpaUid         = '700000012345';
        $this->actorUid       = '700000055554';
        $this->lpaActorToken  = '00000000-0000-4000-A000-000000000000';
        $this->additionalInfo = "This is a notes field with \n information about the user \n over multiple lines";

        $this->twoWeekInterval = new DateInterval('P2W');
        $this->sixWeekInterval = new DateInterval('P6W');
        $this->oneYearInterval = new DateInterval('P1Y');

        $this->dataToMatch = [
            'reference_number'     => $this->lpaUid,
            'dob'                  => '1980-03-01',
            'first_names'          => 'Test Tester',
            'last_name'            => 'Testing',
            'postcode'             => 'Ab1 2Cd',
            'force_activation_key' => false,
        ];
    }

    private function getOlderLpaService(): AccessForAllLpaService
    {
        return new AccessForAllLpaService(
            $this->actorCodesProphecy->reveal(),
            $this->lpasInterfaceProphecy->reveal(),
            $this->userLpaActorMapProphecy->reveal(),
            $this->featureEnabledProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );
    }

    /** @test */
    public function request_access_code_letter(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid, null)
            ->shouldBeCalled();

        $this->userLpaActorMapProphecy->create(
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            $this->oneYearInterval,
            $this->twoWeekInterval,
        )->willReturn($this->lpaActorToken);

        $service = $this->getOlderLpaService();
        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId);
    }

    /** @test */
    public function request_cleanse_and_access_code_letter(): void
    {
        $this->userLpaActorMapProphecy->create(
            $this->userId,
            $this->lpaUid,
            null,
            $this->oneYearInterval,
            $this->sixWeekInterval
        )->willReturn($this->lpaActorToken);

        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, null, $this->additionalInfo)
            ->shouldBeCalled();

        $this->userLpaActorMapProphecy->updateRecord(
            $this->lpaActorToken,
            $this->oneYearInterval,
            $this->sixWeekInterval,
            null
        )->shouldNotBeCalled();

        $service = $this->getOlderLpaService();
        $service->requestAccessAndCleanseByLetter($this->lpaUid, $this->userId, $this->additionalInfo);
    }

    /** @test */
    public function request_access_code_letter_record_exists(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid, null)
            ->shouldBeCalled();

        $this->userLpaActorMapProphecy->create(Argument::cetera())->shouldNotBeCalled();

        $this->userLpaActorMapProphecy->updateRecord(
            'token-12345',
            $this->oneYearInterval,
            $this->twoWeekInterval,
            $this->actorUid
        )->shouldBeCalled();

        $service = $this->getOlderLpaService();
        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId, 'token-12345');
    }

    /** @test */
    public function request_access_code_letter_and_cleanse_record_exists(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, null, $this->additionalInfo)
            ->shouldBeCalled();

        $this->userLpaActorMapProphecy->create(Argument::cetera())->shouldNotBeCalled();

        $this->userLpaActorMapProphecy->updateRecord(
            'token-12345',
            $this->oneYearInterval,
            $this->sixWeekInterval,
            null
        )->shouldBeCalled();

        $service = $this->getOlderLpaService();
        $service->requestAccessAndCleanseByLetter(
            $this->lpaUid,
            $this->userId,
            $this->additionalInfo,
            null,
            'token-12345'
        );
    }

    /** @test */
    public function request_access_code_letter_without_flag(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid, null);

        $this->userLpaActorMapProphecy->create(Argument::cetera())->shouldNotBeCalled();

        $this->userLpaActorMapProphecy->updateRecord(
            'token-12345',
            $this->oneYearInterval,
            $this->twoWeekInterval,
            $this->actorUid
        )->shouldBeCalled();

        $service = $this->getOlderLpaService();
        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId, 'token-12345');
    }

    /** @test */
    public function request_access_code_letter_api_call_fails(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid, null)
            ->willThrow(ApiException::create('bad api call'));

        $this->userLpaActorMapProphecy->create(
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            $this->oneYearInterval,
            $this->twoWeekInterval
        )->willReturn($this->lpaActorToken);

        $this->userLpaActorMapProphecy
            ->delete($this->lpaActorToken)
            ->shouldBeCalled()
            ->willReturn([]);

        $service = $this->getOlderLpaService();

        $this->expectException(ApiException::class);

        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId);
    }

    /** @test */
    public function request_access_code_letter_and_cleanse_api_call_fails(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, null, $this->additionalInfo)
            ->willThrow(ApiException::create('bad api call'));

        $this->userLpaActorMapProphecy->create(
            $this->userId,
            $this->lpaUid,
            null,
            $this->oneYearInterval,
            $this->sixWeekInterval
        )->willReturn($this->lpaActorToken);

        $this->userLpaActorMapProphecy
            ->delete($this->lpaActorToken)
            ->shouldBeCalled()
            ->willReturn([]);

        $service = $this->getOlderLpaService();

        $this->expectException(ApiException::class);

        $service->requestAccessAndCleanseByLetter($this->lpaUid, $this->userId, $this->additionalInfo);
    }

    /** @test */
    public function returns_code_created_date_if_code_exists_for_actor(): void
    {
        $createdDate = (new DateTime('now'))->modify('-15 days')->format('Y-m-d');

        $lpaCodesResponse = new ActorCode(
            [
                'Created' => $createdDate,
            ],
            new DateTime('now')
        );

        $this->actorCodesProphecy
            ->checkActorHasCode($this->lpaUid, $this->actorUid)
            ->willReturn($lpaCodesResponse);

        $service = $this->getOlderLpaService();

        $codeCreated = $service->hasActivationCode($this->lpaUid, $this->actorUid);
        $this->assertEquals(DateTime::createFromFormat('Y-m-d', $createdDate), $codeCreated);
    }

    /** @test */
    public function returns_null_if_a_code_does_not_exist_for_an_actor(): void
    {
        $lpaCodesResponse = new ActorCode(
            [
                'Created' => null,
            ],
            new DateTime()
        );

        $this->actorCodesProphecy
            ->checkActorHasCode($this->lpaUid, $this->actorUid)
            ->willReturn($lpaCodesResponse);

        $service = $this->getOlderLpaService();

        $codeExists = $service->hasActivationCode($this->lpaUid, $this->actorUid);
        $this->assertNull($codeExists);
    }
}
