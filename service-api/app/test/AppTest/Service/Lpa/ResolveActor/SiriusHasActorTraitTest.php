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
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class SiriusHasActorTraitTest extends TestCase
{
    use ProphecyTrait;

    private HasActorInterface $mock;

    protected function setUp(): void
    {
        $this->mock = new class (
            $this->prophesize(LoggerInterface::class)->reveal()
        ) implements HasActorInterface {
            use SiriusHasActorTrait;

            public function __construct(private LoggerInterface $logger)
            {
            }

            private function getDonor(): SiriusPerson
            {
                return new SiriusPerson(
                    [
                        'id'     => 1,
                        'uId'    => '123456789',
                        'linked' => [['id' => 1, 'uId' => '123456789'], ['id' => 2, 'uId' => '234567890']],
                    ],
                    $this->logger,
                );
            }

            private function getAttorneys(): array
            {
                return [
                    new SiriusPerson(
                        ['id' => 3, 'uId' => '345678901', 'firstname' => 'A', 'surname' => 'B'],
                        $this->logger,
                    ),
                    new SiriusPerson(
                        ['id' => 4, 'uId' => '456789012', 'firstname' => 'B', 'surname' => 'C'],
                        $this->logger,
                    ),
                    new SiriusPerson(
                        ['id' => 5, 'uId' => '567890123', 'firstname' => 'C', 'surname' => 'D'],
                        $this->logger,
                    ),
                ];
            }

            private function getTrustCorporations(): array
            {
                return [
                    new SiriusPerson(
                        ['id' => 6, 'uId' => '678901234', 'companyName' => 'A'],
                        $this->logger,
                    ),
                    new SiriusPerson(
                        ['id' => 7, 'uId' => '789012345', 'companyName' => 'B'],
                        $this->logger,
                    ),
                ];
            }
        };
    }

    #[Test]
    public function does_not_find_nonexistant_actor(): void
    {
        $result = $this->mock->hasActor('012345678');

        $this->assertNotInstanceOf(LpaActor::class, $result);
    }

    #[Test]
    public function finds_a_donor_actor(): void
    {
        $result = $this->mock->hasActor('123456789');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals(1, $result->actor['id']);
        $this->assertSame(ActorType::DONOR, $result->actorType);
    }

    #[Test]
    public function finds_an_attorney_actor(): void
    {
        $result = $this->mock->hasActor('456789012');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor['firstname']);
        $this->assertSame(ActorType::ATTORNEY, $result->actorType);
    }

    #[Test]
    public function finds_a_trust_corporation_actor(): void
    {
        $result = $this->mock->hasActor('789012345');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor['companyName']);
        $this->assertSame(ActorType::TRUST_CORPORATION, $result->actorType);
    }
}
