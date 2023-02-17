<?php

declare(strict_types=1);

namespace AppTest\Service\ViewerCodes;

use App\DataAccess\Repository\KeyCollisionException;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\DataAccess\Repository\ViewerCodeActivityInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Service\ViewerCodes\ViewerCodeService;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class ViewerCodeServiceTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_ca_be_instantiated(): void
    {
        $viewerCodeRepoProphecy           = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeActivityRepoProphecy   = $this->prophesize(ViewerCodeActivityInterface::class);
        $userActorLpaRepoProphecy         = $this->prophesize(UserLpaActorMapInterface::class);
        $loggerProphecy                   = $this->prophesize(LoggerInterface::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $viewerCodeActivityRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $loggerProphecy->reveal(),
        );

        $this->assertInstanceOf(ViewerCodeService::class, $service);
    }

    /** @test */
    public function it_will_make_a_new_viewer_code_for_an_lpa(): void
    {
        // code will expire 30 days from midnight of the day the test runs
        $codeExpiry = new DateTime(
            '23:59:59 +30 days',                // Set to the last moment of the day, x days from now.
            new DateTimeZone('Europe/London')   // Ensures we compensate for GMT vs BST.
        );

        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->add(
                Argument::type('string'),
                'id',
                '700000000047',
                Argument::exact($codeExpiry),
                'token name',
                '1234'
            )
            ->shouldBeCalled();

        $viewerCodeActivityRepoProphecy = $this->prophesize(ViewerCodeActivityInterface::class);

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'user_id',
                    'SiriusUid' => '700000000047',
                    'ActorId'   => '1234'
                ]
            );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $viewerCodeActivityRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $loggerProphecy->reveal(),
        );

        $result = $service->addCode('user_actor_lpa_token', 'user_id', 'token name');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('expires', $result);
        $this->assertArrayHasKey('organisation', $result);
        $this->assertEquals('token name', $result['organisation']);
    }

    /** @test */
    public function it_wont_create_a_code_if_user_does_not_match(): void
    {
        $viewerCodeRepoProphecy         = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeActivityRepoProphecy = $this->prophesize(ViewerCodeActivityInterface::class);
        $userActorLpaRepoProphecy       = $this->prophesize(UserLpaActorMapInterface::class);

        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'another_user_id',
                    'SiriusUid' => '700000000047',
                ]
            );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $viewerCodeActivityRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $loggerProphecy->reveal(),
        );

        $result = $service->addCode('user_actor_lpa_token', 'user_id', 'token name');

        $this->assertNull($result);
    }

    /** @test */
    public function it_will_generate_codes_until_a_new_one_is_found(): void
    {
        $callCount = 0;
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->add(
                Argument::type('string'),
                'id',
                '700000000047',
                Argument::type(DateTime::class),
                'token name',
                '1234'
            )
            ->shouldBeCalledTimes(2)
            ->will(function () use (&$callCount) {
                if ($callCount >= 1) {
                    return;
                }

                $callCount++;
                throw new KeyCollisionException();
            });

        $viewerCodeActivityRepoProphecy = $this->prophesize(ViewerCodeActivityInterface::class);

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'user_id',
                    'SiriusUid' => '700000000047',
                    'ActorId'   => '1234'
                ]
            );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $viewerCodeActivityRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $loggerProphecy->reveal(),
        );

        $result = $service->addCode('user_actor_lpa_token', 'user_id', 'token name');

        $this->assertTrue($callCount > 0);
    }

    /** @test */
    public function it_will_retrieve_codes_of_an_lpa(): void
    {
        $codeExpiry = new DateTimeImmutable(
            '23:59:59 +29 days',          // Set to the last moment of the day, x days from now.
            new DateTimeZone('Europe/London')   // Ensures we compensate for GMT vs BST.
        );

        $viewerCodes = [
            [   // a complete and unexpired code that has been viewed
                'SiriusUid'     => '700000000047',
                'Added'         => (new DateTimeImmutable('-1 day'))->format('c'),
                'ViewerCode'    => 'abcdefghijkl',
                'Organisation'  => 'My bank',
                'UserLpaActor'  => '3f0455d4-611f-11ed-9b6a-0242ac120002',
                'Expires'       => $codeExpiry->format('c'),
            ],
            [   // a complete and valid code that has not been viewed
                'SiriusUid'     => '700000000047',
                'Added'         => (new DateTimeImmutable('-1 day'))->format('c'),
                'ViewerCode'    => '123456789101',
                'Organisation'  => 'My gas company',
                'UserLpaActor'  => '',
                'Expires'       => $codeExpiry->format('c'),
                'CreatedBy'     => ''
            ],
            [   // a code that does not map to a user record (orphaned)
                'SiriusUid'     => '700000000047',
                'Added'         => (new DateTimeImmutable('-1 day'))->format('c'),
                'ViewerCode'    => 'asdfghjklzxc',
                'Organisation'  => 'The council',
                'UserLpaActor'  => '19d2d742-437e-438f-8f15-e43e658dcd5b',
                'Expires'       => $codeExpiry->format('c'),
            ],
            [   // a code that corresponds to a deleted lpa and does not have access code owner details
                'SiriusUid'     => '700000000047',
                'Added'         => (new DateTimeImmutable('-1 day'))->format('c'),
                'ViewerCode'    => 'abcdefghijkl',
                'Organisation'  => 'The council',
                'Expires'       => $codeExpiry->format('c'),
                'CreatedBy'     => '23'
            ],
        ];

        $activities = [
            [
                'ViewerCode' => 'abcdefghijkl',
                'Viewed'     => [
                    'Viewed'        => (new DateTimeImmutable('now'))->format('c'),
                    'ViewerCode'    => 'abcdefghijkl',
                    'ViewedBy'      => 'Bank',
                ],
            ],
            [
                'ViewerCode' => '123456789101',
                'Viewed'     => false,
            ],
            [
                'ViewerCode' => 'asdfghjklzxc',
                'Viewed'     => false,
            ],
        ];

        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->getCodesByLpaId('700000000047')
            ->shouldBeCalled()
            ->willReturn($viewerCodes);

        // merge our two data fixtures like the repo does
        $mergedFixtures = array_reduce(
            $activities,
            function (array $codes, array $activity) {
                foreach ($codes as $index => $code) {
                    if ($code['ViewerCode'] === $activity['ViewerCode']) {
                        $codes[$index]['Viewed'] = $activity['Viewed'];
                    }
                }

                return $codes;
            },
            $viewerCodes,
        );

        $viewerCodeActivityRepoProphecy = $this->prophesize(ViewerCodeActivityInterface::class);
        $viewerCodeActivityRepoProphecy
            ->getStatusesForViewerCodes($viewerCodes)
            ->shouldBeCalled()
            ->willReturn($mergedFixtures);

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('3f0455d4-611f-11ed-9b6a-0242ac120002')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'ActorId'   => '12',
                    'UserId'    => 'user_id',
                    'SiriusUid' => '700000000047',
                ]
            );

        $userActorLpaRepoProphecy
            ->get('19d2d742-437e-438f-8f15-e43e658dcd5b')
            ->shouldBeCalled()
            ->willReturn(null);

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $viewerCodeActivityRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $loggerProphecy->reveal(),
        );

        $codes = $service->getCodes('3f0455d4-611f-11ed-9b6a-0242ac120002', 'user_id');

        $this->assertEquals('abcdefghijkl', $codes[0]['ViewerCode']);
        $this->assertEquals(12, $codes[0]['ActorId']);
        $this->assertIsArray($codes[0]['Viewed']);

        $this->assertEquals('123456789101', $codes[1]['ViewerCode']);

        $this->assertArrayNotHasKey('ActorId', $codes[1]);
        $this->assertFalse($codes[1]['Viewed']);

        $this->assertEquals('asdfghjklzxc', $codes[2]['ViewerCode']);
        $this->assertArrayNotHasKey('ActorId', $codes[2]);
        $this->assertFalse($codes[2]['Viewed']);

        $this->assertArrayHasKey('CreatedBy', $codes[3]);
        $this->assertArrayHasKey('ActorId', $codes[3]);
        $this->assertEquals($codes[3]['CreatedBy'], $codes[3]['ActorId']);
    }

    /** @test */
    public function it_wont_get_codes_if_user_does_not_match(): void
    {
        $viewerCodeRepoProphecy         = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeActivityRepoProphecy = $this->prophesize(ViewerCodeActivityInterface::class);

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'another_user_id',
                    'SiriusUid' => '700000000047',
                ]
            );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $viewerCodeActivityRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $loggerProphecy->reveal(),
        );

        $codes = $service->getCodes('user_actor_lpa_token', 'user_id');

        $this->assertNull($codes);
    }

    /** @test */
    public function it_can_cancel_a_code(): void
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->get('123412341234')
            ->shouldBeCalled()
            ->willReturn([]);
        $viewerCodeRepoProphecy
            ->cancel('123412341234', Argument::type(DateTime::class))
            ->shouldBeCalled();

        $viewerCodeActivityRepoProphecy = $this->prophesize(ViewerCodeActivityInterface::class);

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'user_id',
                    'SiriusUid' => '700000000047',
                ]
            );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $viewerCodeActivityRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $loggerProphecy->reveal(),
        );

        $service->cancelCode('user_actor_lpa_token', 'user_id', '123412341234');
    }

    /** @test */
    public function it_wont_cancel_a_code_if_the_user_does_not_match(): void
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->cancel('123412341234', Argument::type(DateTime::class))
            ->shouldNotBeCalled();

        $viewerCodeActivityRepoProphecy = $this->prophesize(ViewerCodeActivityInterface::class);

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'another_user_id',
                    'SiriusUid' => '700000000047',
                ]
            );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $viewerCodeActivityRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $loggerProphecy->reveal(),
        );

        $service->cancelCode('user_actor_lpa_token', 'user_id', '123412341234');
    }

    /** @test */
    public function it_wont_cancel_a_code_if_it_cant_find_it(): void
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $viewerCodeRepoProphecy
            ->get('123412341234')
            ->shouldBeCalled()
            ->willReturn(null);
        $viewerCodeRepoProphecy
            ->cancel('123412341234', Argument::type(DateTime::class))
            ->shouldNotBeCalled();

        $viewerCodeActivityRepoProphecy = $this->prophesize(ViewerCodeActivityInterface::class);

        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaRepoProphecy
            ->get('user_actor_lpa_token')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'Id'        => 'id',
                    'UserId'    => 'user_id',
                    'SiriusUid' => '700000000047',
                ]
            );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $service = new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $viewerCodeActivityRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $loggerProphecy->reveal(),
        );

        $service->cancelCode('user_actor_lpa_token', 'user_id', '123412341234');
    }
}
