<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\ResolveActor;

use App\Service\Lpa\ResolveActor\ActorType;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\ResolveActor\LpaActor;
use App\Service\Lpa\ResolveActor\SiriusHasActorTrait;
use App\Service\Lpa\SiriusPerson;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SiriusHasActorTraitTest extends TestCase
{
    private HasActorInterface $mock;

    public function setUp(): void
    {
        $this->mock = new class () implements HasActorInterface {
            use SiriusHasActorTrait;

            private function getDonor(): SiriusPerson
            {
                return new SiriusPerson([
                    'id'     => 1,
                    'uId'    => '123456789',
                    'linked' => [['id' => 1, 'uId' => '123456789'], ['id' => 2, 'uId' => '234567890']],
                ]);
            }

            private function getAttorneys(): array
            {
                return [
                    new SiriusPerson(['id' => 3, 'uId' => '345678901', 'firstname' => 'A', 'surname' => 'B']),
                    new SiriusPerson(['id' => 4, 'uId' => '456789012', 'firstname' => 'B', 'surname' => 'C']),
                    new SiriusPerson(['id' => 5, 'uId' => '567890123', 'firstname' => 'C', 'surname' => 'D']),
                ];
            }

            private function getTrustCorporations(): array
            {
                return [
                    new SiriusPerson(['id' => 6, 'uId' => '678901234', 'companyName' => 'A']),
                    new SiriusPerson(['id' => 7, 'uId' => '789012345', 'companyName' => 'B']),
                ];
            }
        };
    }

    #[Test]
    public function does_not_find_nonexistant_actor(): void
    {
        $result = $this->mock->hasActor('012345678');

        $this->assertNull($result);
    }

    #[Test]
    public function finds_a_donor_actor(): void
    {
        $result = $this->mock->hasActor('123456789');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals(1, $result->actor['id']);
        $this->assertEquals(ActorType::DONOR, $result->actorType);
    }

    #[Test]
    public function finds_an_attorney_actor(): void
    {
        $result = $this->mock->hasActor('456789012');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor['firstname']);
        $this->assertEquals(ActorType::ATTORNEY, $result->actorType);
    }

    #[Test]
    public function finds_a_trust_corporation_actor(): void
    {
        $result = $this->mock->hasActor('789012345');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor['companyName']);
        $this->assertEquals(ActorType::TRUST_CORPORATION, $result->actorType);
    }
}
