<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\ResolveActor;
use App\Service\Lpa\ResolveActor\ActorType;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\ResolveActor\LpaActor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ResolveActorTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_finds_an_actor(): void
    {
        $hasActorInterfaceProphecy = $this->prophesize(HasActorInterface::class);
        $hasActorInterfaceProphecy
            ->hasActor('uid-1')
            ->willReturn(
                new LpaActor(
                    [
                        'uid' => 'uid-1',
                    ],
                    ActorType::ATTORNEY
                )
            );

        $sut    = new ResolveActor();
        $result = $sut($hasActorInterfaceProphecy->reveal(), 'uid-1');

        $this->assertInstanceOf(LpaActor::class, $result);
    }
}
