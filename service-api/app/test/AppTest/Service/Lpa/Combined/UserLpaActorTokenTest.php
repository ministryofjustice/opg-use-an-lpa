<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\Combined;

use App\Service\Lpa\Combined\UserLpaActorToken;
use App\Service\Lpa\ResolveActor\ActorType;
use App\Service\Lpa\ResolveActor\LpaActor;
use AppTest\LpaUtilities;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserLpaActorTokenTest extends TestCase
{
    #[Test]
    public function it_can_be_initialised(): void
    {
        $token = '1234';
        $date  = new DateTimeImmutable('now');
        $lpa   = LpaUtilities::lpaStoreLpaFixture();

        $sut  = new UserLpaActorToken($token, $date, $lpa);
        $json = json_encode($sut);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame($token, $decoded['user-lpa-actor-token']);
        $this->assertEquals($date->format(DateTimeInterface::ATOM), $decoded['date']);
        $this->assertEquals($lpa->getUid(), $decoded['lpa']['uId']);
    }

    #[Test]
    public function an_activation_due_date_can_be_added(): void
    {
        $token = '1234';
        $date  = new DateTimeImmutable('now');
        $lpa   = LpaUtilities::lpaStoreLpaFixture();

        $sut     = new UserLpaActorToken($token, $date, $lpa);
        $dueDate = new DateTimeImmutable('now +20 days');
        $sut     = $sut->withActivationKeyDueDate($dueDate);

        $json = json_encode($sut);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame($decoded['activationKeyDueDate'], $dueDate->format(DateTimeInterface::ATOM));
    }

    #[Test]
    public function an_actor_can_be_added(): void
    {
        $token = '1234';
        $date  = new DateTimeImmutable('now');
        $lpa   = LpaUtilities::lpaStoreLpaFixture();

        $sut   = new UserLpaActorToken($token, $date, $lpa);
        $actor = new LpaActor([], ActorType::ATTORNEY);
        $sut   = $sut->withActor($actor);

        $json = json_encode($sut);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('actor', $decoded);
        $this->assertSame(ActorType::ATTORNEY->value, $decoded['actor']['type']);
    }

    #[Test]
    public function a_paper_verification_flag_can_be_added(): void
    {
        $token = '1234';
        $date  = new DateTimeImmutable('now');
        $lpa   = LpaUtilities::lpaStoreLpaFixture();

        $sut = new UserLpaActorToken($token, $date, $lpa);
        $sut = $sut->withHasPaperVerificationCode(true);

        $json = json_encode($sut);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertTrue($decoded['hasPaperVerificationCode']);
    }
}
