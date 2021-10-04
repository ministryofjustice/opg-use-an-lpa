<?php

namespace AppTest\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\ActorCode;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\OlderLpaService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class OlderLpaServiceTest extends TestCase
{
    /** @var ObjectProphecy|FeatureEnabled */
    private $featureEnabledProphecy;

    /** @var ObjectProphecy|LpasInterface */
    private $lpasInterfaceProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    /** @var ObjectProphecy|ActorCodes */
    public $actorCodesProphecy;

    /** @var UserLpaActorMapInterface|ObjectProphecy */
    private $userLpaActorMapProphecy;

    public string $userId;
    public string $lpaUid;
    public string $actorUid;
    public array $dataToMatch;

    public function setUp()
    {
        $this->lpasInterfaceProphecy = $this->prophesize(LpasInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->actorCodesProphecy = $this->prophesize(ActorCodes::class);
        $this->userLpaActorMapProphecy = $this->prophesize(UserLpaActorMap::class);
        $this->featureEnabledProphecy = $this->prophesize(FeatureEnabled::class);

        $this->userId = 'user-zxywq-54321';
        $this->lpaUid = '700000012345';
        $this->actorUid = '700000055554';

        $this->dataToMatch = [
            'reference_number'      => $this->lpaUid,
            'dob'                   => '1980-03-01',
            'first_names'           => 'Test Tester',
            'last_name'             => 'Testing',
            'postcode'              => 'Ab1 2Cd',
            'force_activation_key'  => false,
        ];
    }

    private function getOlderLpaService(): OlderLpaService
    {
        return new OlderLpaService(
            $this->actorCodesProphecy->reveal(),
            $this->lpasInterfaceProphecy->reveal(),
            $this->userLpaActorMapProphecy->reveal(),
            $this->featureEnabledProphecy->reveal(),
            $this->loggerProphecy->reveal(),
        );
    }

    /** @test */
    public function request_access_code_letter(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid)
            ->shouldBeCalled();

        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(true);

        $this->userLpaActorMapProphecy->create(
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->willReturn('00000000-0000-4000-A000-000000000000');

        $service = $this->getOlderLpaService();
        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId);
    }

    /** @test */
    public function request_access_code_letter_without_flag(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid)
            ->shouldBeCalled();

        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(false);

        $this->userLpaActorMapProphecy->create(
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->shouldNotBeCalled();

        $service = $this->getOlderLpaService();
        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId);
    }

    /** @test */
    public function request_access_code_letter_api_call_fails(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid)
            ->willThrow(ApiException::create('bad api call'));

        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(true);

        $this->userLpaActorMapProphecy->create(
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->willReturn('00000000-0000-4000-A000-000000000000');

        $this->userLpaActorMapProphecy
            ->delete('00000000-0000-4000-A000-000000000000')
            ->shouldBeCalled()
            ->willReturn([]);

        $service = $this->getOlderLpaService();

        $this->expectException(ApiException::class);

        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId);
    }

    /** @test */
    public function request_access_code_letter_api_call_fails_without_flag(): void
    {
        $this->lpasInterfaceProphecy
            ->requestLetter((int) $this->lpaUid, (int) $this->actorUid)
            ->willThrow(ApiException::create('bad api call'));

        $service = $this->getOlderLpaService();

        $this->expectException(ApiException::class);

        $this->userLpaActorMapProphecy->create(
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string')
        )->shouldNotBeCalled();

        $this->userLpaActorMapProphecy->delete(Argument::type('string'))->willReturn([])->shouldNotBeCalled();

        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(false);

        $service->requestAccessByLetter($this->lpaUid, $this->actorUid, $this->userId);
    }

    /** @test */
    public function returns_code_created_date_if_code_exists_for_actor()
    {
        $createdDate = (new DateTime('now'))->modify('-15 days')->format('Y-m-d');

        $lpaCodesResponse = new ActorCode(
            [
                'Created' => $createdDate
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
    public function returns_null_if_a_code_does_not_exist_for_an_actor()
    {
        $lpaCodesResponse = new ActorCode(
            [
                'Created' => null
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
