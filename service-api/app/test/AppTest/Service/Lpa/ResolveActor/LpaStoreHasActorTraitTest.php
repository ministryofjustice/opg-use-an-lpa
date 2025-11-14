<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\ResolveActor;

use App\Entity\LpaStore\LpaStoreAttorney;
use App\Entity\LpaStore\LpaStoreDonor;
use App\Entity\LpaStore\LpaStoreTrustCorporation;
use App\Enum\ActorStatus;
use App\Service\Lpa\ResolveActor\ActorType;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\ResolveActor\LpaActor;
use App\Service\Lpa\ResolveActor\LpaStoreHasActorTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class LpaStoreHasActorTraitTest extends TestCase
{
    use ProphecyTrait;

    private HasActorInterface $mock;

    public function setUp(): void
    {
        $this->mock = new class (
            $this->prophesize(LoggerInterface::class)->reveal()
        ) implements HasActorInterface {
            use LpaStoreHasActorTrait;

            public function __construct(private LoggerInterface $logger)
            {
                $this->attorneys         = $this->getAttorneys();
                $this->trustCorporations = $this->getTrustCorporations();
                $this->donor             = $this->getDonor();
            }

            private function getDonor(): LpaStoreDonor
            {
                return new LpaStoreDonor(
                    line1: '81 Front Street',
                    line2: 'LACEBY',
                    line3: '',
                    country:      '',
                    county:       '',
                    dateOfBirth:          null,
                    email:        'RachelSanderson@opgtest.com',
                    firstNames:   'Rachel',
                    otherNamesKnownBy: '',
                    postcode:     'DN37 5SH',
                    lastName:      'Sanderson',
                    town:         '',
                    uId:          '123456789',
                );
            }

            private function getAttorneys(): array
            {
                return [
                    new LpaStoreAttorney(
                        line1:       '',
                        line2:       '',
                        line3:       '',
                        country:     '',
                        county:      '',
                        dateOfBirth: null,
                        email:       'XXXXX',
                        firstNames:  'B',
                        postcode:    '',
                        lastName:    'C',
                        status:      ActorStatus::ACTIVE,
                        town:        '',
                        uId:         '345678901',
                    ),
                    new LpaStoreAttorney(
                        line1:       '',
                        line2:       '',
                        line3:       '',
                        country:     '',
                        county:      '',
                        dateOfBirth: null,
                        email:       'XXXXX',
                        firstNames:  'B',
                        postcode:    '',
                        lastName:    'C',
                        status:      ActorStatus::ACTIVE,
                        town:        '',
                        uId:         '456789012',
                    ),
                    new LpaStoreAttorney(
                        line1:       '',
                        line2:       '',
                        line3:       '',
                        country:     '',
                        county:      '',
                        dateOfBirth: null,
                        email:       'XXXXX',
                        firstNames:  'B',
                        postcode:    '',
                        lastName:    'C',
                        status:      ActorStatus::ACTIVE,
                        town:        '',
                        uId:         '7567890123',
                    ),
                ];
            }

            private function getTrustCorporations(): array
            {
                return [
                    new LpaStoreTrustCorporation(
                        line1:       'Street 1',
                        line2:       'Street 2',
                        line3:       'Street 3',
                        country:     'GB',
                        county:      'County',
                        dateOfBirth: null,
                        email:       null,
                        firstNames:  'trust',
                        name:        'B',
                        postcode:    'ABC 123',
                        lastName:    'test',
                        status:      ActorStatus::ACTIVE,
                        town:        'Town',
                        uId:         '678901234',
                    ),
                    new LpaStoreTrustCorporation(
                        line1:       'Street 1',
                        line2:       'Street 2',
                        line3:       'Street 3',
                        country:     'GB',
                        county:      'County',
                        dateOfBirth: null,
                        email:       null,
                        firstNames:  'trust',
                        name:        'B',
                        postcode:    'ABC 123',
                        lastName:    'test',
                        status:      ActorStatus::ACTIVE,
                        town:        'Town',
                        uId:         '789012345',
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
        $this->assertSame(ActorType::DONOR, $result->actorType);
    }

    #[Test]
    public function finds_an_attorney_actor(): void
    {
        $result = $this->mock->hasActor('456789012');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor->firstnames);
        $this->assertSame(ActorType::ATTORNEY, $result->actorType);
    }

    #[Test]
    public function finds_a_trust_corporation_actor(): void
    {
        $result = $this->mock->hasActor('789012345');

        $this->assertInstanceOf(LpaActor::class, $result);
        $this->assertEquals('B', $result->actor->name);
        $this->assertSame(ActorType::TRUST_CORPORATION, $result->actorType);
    }
}
