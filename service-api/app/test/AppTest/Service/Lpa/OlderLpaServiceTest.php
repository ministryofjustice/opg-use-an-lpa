<?php

namespace AppTest\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\Repository\KeyCollisionException;
use App\DataAccess\Repository\KeyCollisionException;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\ActorCode;
use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Lpa\ActivationKeyAlreadyRequested;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\GetAttorneyStatus;
use App\Service\Lpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\OlderLpaService;
use App\Service\Lpa\ValidateOlderLpaRequirements;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use App\Service\Features\FeatureEnabled;

class OlderLpaServiceTest extends TestCase
{
    /** @var ObjectProphecy|FeatureEnabled */
    private $featureEnabledProphecy;

    /** @var ObjectProphecy|LpaAlreadyAdded */
    private $lpaAlreadyAddedProphecy;

    /** @var ObjectProphecy|LpaService */
    private $lpaServiceProphecy;

    /** @var ObjectProphecy|LpasInterface */
    private $lpasInterfaceProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    /** @var ObjectProphecy|ActorCodes */
    public $actorCodesProphecy;

    /** @var ObjectProphecy|GetAttorneyStatus */
    private $getAttorneyStatusProphecy;

    /** @var ObjectProphecy|ValidateOlderLpaRequirements */
    private $validateOlderLpaRequirementsProphecy;

    /** @var UserLpaActorMapInterface|ObjectProphecy */
    private $userLpaActorMapProphecy;

    /** @var ObjectProphecy|ActivationKeyAlreadyRequested */
    private $activationKeyAlreadyRequestedProphecy;

    /** @var ObjectProphecy|AddOlderLpa */
    private $addOlderLpaProphecy;

    public string $userId;
    public string $lpaUid;
    public string $actorUid;

    public function setUp()
    {
        $this->lpasInterfaceProphecy = $this->prophesize(LpasInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->actorCodesProphecy = $this->prophesize(ActorCodes::class);
        $this->getAttorneyStatusProphecy = $this->prophesize(GetAttorneyStatus::class);
        $this->activationKeyAlreadyRequestedProphecy = $this->prophesize(ActivationKeyAlreadyRequested::class);
        $this->addOlderLpaProphecy = $this->prophesize(AddOlderLpa::class);

        $this->userId = 'user-zxywq-54321';
        $this->lpaUid = '700000012345';
        $this->actorUid = '700000055554';
    }

    private function getOlderLpaService(): OlderLpaService
    {
        return new OlderLpaService(
            $this->actorCodesProphecy->reveal(),
            $this->getAttorneyStatusProphecy->reveal(),
            $this->activationKeyAlreadyRequestedProphecy->reveal(),
            $this->addOlderLpaProphecy->reveal(),
            $this->lpasInterfaceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->featureEnabled->reveal(),
            $this->validateOlderLpaRequirementsProphecy->reveal(),
            $this->featureEnabledProphecy->reveal()
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
            Argument::type('string'),
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->shouldBeCalled();

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
            Argument::type('string'),
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

        $service = $this->getOlderLpaService();

        $this->expectException(ApiException::class);

        $this->userLpaActorMapProphecy->create(
            Argument::type('string'),
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->shouldBeCalled();

        $this->userLpaActorMapProphecy->delete(Argument::type('string'))->willReturn([])->shouldBeCalled();

        $this->featureEnabledProphecy->__invoke('save_older_lpa_requests')->willReturn(true);

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

    /**
     * @test
     */
    public function older_lpa_request_is_saved_with_a_TTL()
    {
        $this->userLpaActorMapProphecy->create(
            Argument::that(
                function (string $id) {
                    $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $id);
                    return true;
                }
            ),
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->shouldBeCalled();

        $service = $this->getOlderLpaService();

        $service->storeLPARequest($this->lpaUid, $this->userId, $this->actorUid);
    }

    /**
     * @test
     */
    public function older_lpa_request_is_looped_until_no_id_collision()
    {
        $createCalls = 0;
        $this->userLpaActorMapProphecy->create(
            Argument::that(
                function (string $id) {
                    $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $id);
                    return true;
                }
            ),
            $this->userId,
            $this->lpaUid,
            $this->actorUid,
            'P1Y'
        )->will(function () use (&$createCalls) {
            if ($createCalls > 0) {
                return;
            }

            $createCalls++;
            throw new KeyCollisionException();
        });

        $service = $this->getOlderLpaService();

        $service->storeLPARequest($this->lpaUid, $this->userId, $this->actorUid);
    }
}
