<?php

declare(strict_types=1);

namespace AppTest\Service\ViewerCodes;

use App\DataAccess\Repository\KeyCollisionException;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Service\Lpa\LpaService;
use App\Service\ViewerCodes\ViewerCodeService;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ViewerCodeServiceTest extends TestCase
{
    /** @test */
    public function it_ca_be_instantiated()
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $lpaServiceProphecy->reveal()
        );

        $this->assertInstanceOf(ViewerCodeService::class, $service);
    }

    /** @test */
    public function it_will_make_a_new_viewer_code_for_an_lpa()
    {
        // code will expire 30 days from midnight of the day the test runs
        $codeExpiry = new DateTime(
            '23:59:59 +30 days',                    // Set to the last moment of the day, x days from now.
            new DateTimeZone('Europe/London')   // Ensures we compensate for GMT vs BST.
        );

        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->add(
                Argument::type('string'),
                'id',
                '700000000047',
                Argument::exact($codeExpiry),
                'token name'
            )
            ->shouldBeCalled();

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'user_id',
                    'SiriusUid' => '700000000047'
                ]
            );

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $lpaServiceProphecy->reveal()
        );

        $result = $service->addCode('user_actor_lpa_token', 'user_id', 'token name');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('expires', $result);
        $this->assertArrayHasKey('organisation', $result);

        $this->assertEquals('token name', $result['organisation']);
    }

    /** @test */
    public function it_wont_create_a_code_if_user_does_not_match()
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'another_user_id',
                    'SiriusUid' => '700000000047'
                ]
            );

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $lpaServiceProphecy->reveal()
        );

        $result = $service->addCode('user_actor_lpa_token', 'user_id', 'token name');

        $this->assertNull($result);
    }

    /** @test */
    public function it_will_generate_codes_until_a_new_one_is_found()
    {
        $callCount = 0;
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->add(
                Argument::type('string'),
                'id',
                '700000000047',
                Argument::type(DateTime::class),
                'token name'
            )
            ->shouldBeCalledTimes(2)
            ->will(function () use (&$callCount) {
                if ($callCount >= 1) {
                    return;
                }

                $callCount++;
                throw new KeyCollisionException();
            });

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'user_id',
                    'SiriusUid' => '700000000047'
                ]
            );

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $lpaServiceProphecy->reveal()
        );

        $result = $service->addCode('user_actor_lpa_token', 'user_id', 'token name');

        $this->assertTrue($callCount > 0);
    }

    /** @test */
    public function it_will_retrieve_codes_of_a_user()
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->getCodesByUserLpaActorId('700000000047', 'user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(['some_lpa_code_data']);

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'user_id',
                    'SiriusUid' => '700000000047'
                ]
            );

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $lpaServiceProphecy->reveal()
        );

        $codes = $service->getCodes('user_actor_lpa_token', 'user_id');

        $this->assertEquals(['some_lpa_code_data'], $codes);
    }

    /** @test */
    public function it_wont_get_codes_if_user_does_not_match()
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'another_user_id',
                    'SiriusUid' => '700000000047'
                ]
            );

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $lpaServiceProphecy->reveal()
        );

        $codes = $service->getCodes('user_actor_lpa_token', 'user_id');

        $this->assertNull($codes);
    }

    /** @test */
    public function it_can_cancel_a_code()
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->get('123412341234')
            ->shouldBeCalled()
            ->willReturn([]);
        $viewerCodeRepoProphecy
            ->cancel('123412341234', Argument::type(\DateTime::class))
            ->shouldBeCalled();

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'user_id',
                    'SiriusUid' => '700000000047'
                ]
            );

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $lpaServiceProphecy->reveal()
        );

        $service->cancelCode('user_actor_lpa_token', 'user_id', '123412341234');
    }

    /** @test */
    public function it_wont_cancel_a_code_if_the_user_does_not_match()
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->cancel('123412341234', Argument::type(\DateTime::class))
            ->shouldNotBeCalled();

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'another_user_id',
                    'SiriusUid' => '700000000047'
                ]
            );

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $lpaServiceProphecy->reveal()
        );

        $service->cancelCode('user_actor_lpa_token', 'user_id', '123412341234');
    }

    /** @test */
    public function it_wont_cancel_a_code_if_it_cant_find_it()
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->get('123412341234')
            ->shouldBeCalled()
            ->willReturn(null);
        $viewerCodeRepoProphecy
            ->cancel('123412341234', Argument::type(\DateTime::class))
            ->shouldNotBeCalled();

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'user_id',
                    'SiriusUid' => '700000000047'
                ]
            );

        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $lpaServiceProphecy->reveal()
        );

        $service->cancelCode('user_actor_lpa_token', 'user_id', '123412341234');
    }
}
