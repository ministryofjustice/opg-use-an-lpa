<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Entity\LpaStore\LpaStoreAttorney;
use App\Entity\LpaStore\LpaStoreDonor;
use App\Entity\Person;
use App\Service\Lpa\FindActorInLpa;
use App\Service\Lpa\GetAttorneyStatus;
use App\Service\Lpa\GetAttorneyStatus\AttorneyStatus;
use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\SiriusPerson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class FindActorInLpaTest extends TestCase
{
    use ProphecyTrait;

    private GetAttorneyStatus|ObjectProphecy $getAttorneyStatusProphecy;
    private LoggerInterface|ObjectProphecy $loggerProphecy;

    public function setUp(): void
    {
        $this->getAttorneyStatusProphecy = $this->prophesize(GetAttorneyStatus::class);
        $this->loggerProphecy            = $this->prophesize(LoggerInterface::class);
    }

    #[Test]
    #[DataProvider('actorLookupDataProviderOldSiriusPerson')]
    public function returns_actor_and_lpa_details_if_match_found(?array $expectedResponse, array $userData): void
    {
        $lpa = [
            'uId'       => '700000012346',
            'donor'     => $this->donorFixture(),
            'attorneys' => [
                $this->inactiveAttorneyFixture(),
                $this->ghostAttorneyFixture(),
                $this->multipleAddressAttorneyFixture(),
                $this->activeAttorneyFixture(),
            ]
        ];

        $this->getAttorneyStatusProphecy
            ->__invoke( $this->inactiveAttorneyFixture())
            ->willReturn(AttorneyStatus::INACTIVE_ATTORNEY);

        $this->getAttorneyStatusProphecy
            ->__invoke( $this->ghostAttorneyFixture())
            ->willReturn(AttorneyStatus::INACTIVE_ATTORNEY);

        $this->getAttorneyStatusProphecy
            ->__invoke( $this->multipleAddressAttorneyFixture())
            ->willReturn(AttorneyStatus::ACTIVE_ATTORNEY);

        $this->getAttorneyStatusProphecy
            ->__invoke( $this->activeAttorneyFixture())
            ->willReturn(AttorneyStatus::ACTIVE_ATTORNEY); // active attorney

        $sut = new FindActorInLpa(
            $this->getAttorneyStatusProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $matchData = $sut(new SiriusLpa($lpa), $userData);
        $this->assertEquals($expectedResponse, $matchData);
    }

    public static function actorLookupDataProviderOldSiriusPerson(): array
    {
        return self::actorLookupDataProvider(FindActorInLpaTest::activeAttorneyFixture(),
                                             FindActorInLpaTest::donorFixture());
    }
    public static function actorLookupDataProvider(SiriusPerson|Person|LpaStoreAttorney $attorneyFixture, SiriusPerson|Person|LpaStoreDonor $donorFixture): array
    {
        return [
            [
                [
                    'actor'  => $attorneyFixture,
                    'role'   => 'attorney', // successful match for attorney
                    'lpa-id' => '700000012346',
                ],
                [
                    'reference_number' => '700000000001',
                    'dob'              => '1980-03-01',
                    'first_names'      => 'Test Tester',
                    'last_name'        => 'T’esting',
                    'postcode'         => 'Ab1 2Cd',
                ],
            ],
            [
                [
                    'actor'  => $donorFixture,
                    'role'   => 'donor', // successful match for donor
                    'lpa-id' => '700000012346',
                ],
                [
                    'reference_number' => '700000000001',
                    'dob'              => '1975-10-05',
                    'first_names'      => 'Donor',
                    'last_name'        => 'Person',
                    'postcode'         => 'PY1 3Kd',
                ],
            ],
            [
                null,
                [
                    'reference_number' => '700000000001',
                    'dob'              => '1982-01-20', // dob will not match
                    'first_names'      => 'Test Tester',
                    'last_name'        => 'Testing',
                    'postcode'         => 'Ab1 2Cd',
                ],
            ],
            [
                null,
                [
                    'reference_number' => '700000000001',
                    'dob'              => '1980-03-01',
                    'first_names'      => 'Wrong', // firstname will not match
                    'last_name'        => 'Testing',
                    'postcode'         => 'Ab1 2Cd',
                ],
            ],
            [
                null,
                [
                    'reference_number' => '700000000001',
                    'dob'              => '1980-03-01',
                    'first_names'      => 'Test Tester',
                    'last_name'        => 'Incorrect', // surname will not match
                    'postcode'         => 'Ab1 2Cd',
                ],
            ],
            [
                null,
                [
                    'reference_number' => '700000000001',
                    'dob'              => '1980-03-01',
                    'first_names'      => 'Test Tester',
                    'last_name'        => 'Testing',
                    'postcode'         => 'WR0 NG1', // postcode will not match
                ],
            ],
            [
                null, // will not find a match as this attorney is inactive
                [
                    'reference_number' => '700000000001',
                    'dob'              => '1977-11-21',
                    'first_names'      => 'Attorneyone',
                    'last_name'        => 'Person',
                    'postcode'         => 'Gg1 2ff',
                ],
            ],
            [
                null, // will not find a match as this attorney is a ghost
                [
                    'reference_number' => '700000000001',
                    'dob'              => '1960-05-05',
                    'first_names'      => 'Attorneytwo',
                    'last_name'        => 'Person',
                    'postcode'         => 'BB1 9ee',
                ],
            ],
        ];
    }

    public static function inactiveAttorneyFixture(): SiriusPerson
    {
        return new SiriusPerson([
            'uId'          => '700000002222',
            'dob'          => '1977-11-21',
            'firstname'    => 'Attorneyone',
            'surname'      => 'Person',
            'addresses'    => [
                [
                    'postcode' => 'Gg1 2ff',
                ],
            ],
            'systemStatus' => false, // inactive attorney
        ]);
    }

    public static function inactiveAttorneyFixtureCombinedSirius(): Person
    {
        return new Person(
            $addressLine1 = null,
            $addressLine2 = null,
            $addressLine3 = null,
            $country = null,
            $county = null,
            $dob = '1977-11-21',
            $email = null,
            $firstname = 'Attorneyone',
            $firstnames = null,
            $name = null,
            $otherNames = null,
            $postcode = 'Gg1 2ff',
            $surname = 'Person',
            $systemStatus = 'false',
            $town = null,
            $type = null,
            $uId = '7000000002222');
    }
    public static function ghostAttorneyFixture(): SiriusPerson
    {
        return new SiriusPerson([
            'uId'          => '700000003333',
            'dob'          => '1960-05-05',
            'firstname'    => '', // ghost attorney
            'surname'      => '',
            'addresses'    => [
                [
                    'postcode' => 'BB1 9ee',
                ],
            ],
            'systemStatus' => true,
        ]);
    }

    public static function ghostAttorneyFixtureCombinedSirius(): Person
    {
        return new Person(
            $addressLine1 = null,
            $addressLine2 = null,
            $addressLine3 = null,
            $country = null,
            $county = null,
            $dob = '1960-05-05',
            $email = null,
            $firstname = '',
            $firstnames = null,
            $name = null,
            $otherNames = null,
            $postcode = 'BB1 9ee',
            $surname = '',
            $systemStatus = 'true',
            $town = null,
            $type = null,
            $uId = '700000003333');
    }
    public static function multipleAddressAttorneyFixture(): SiriusPerson
    {
        return new SiriusPerson([
            'uId'          => '700000004444',
            'dob'          => '1980-03-01',
            'firstname'    => 'Attorneythree',
            'surname'      => 'Person',
            'addresses'    => [ // multiple addresses
                [
                    'postcode' => 'Ab1 2Cd',
                ],
                [
                    'postcode' => 'Bc2 3Df',
                ],
            ],
            'systemStatus' => true,
        ]);
    }
    public static function activeAttorneyFixture(): SiriusPerson
    {
        return new SiriusPerson([
            'uId'          => '700000001234',
            'dob'          => '1980-03-01',
            'firstname'    => 'Test',
            'surname'      => 'T’esting',
            'addresses'    => [
                [
                    'postcode' => 'Ab1 2Cd',
                ],
            ],
            'systemStatus' => true,
        ]);
    }

    public static function activeAttorneyFixtureCombinedSirius(): Person
    {
        return new Person(
            $addressLine1 = null,
            $addressLine2 = null,
            $addressLine3 = null,
            $country = null,
            $county = null,
            $dob = '1980-03-01',
            $email = null,
            $firstname = 'Test',
            $firstnames = null,
            $name = null,
            $otherNames = null,
            $postcode = 'Ab1 2Cd',
            $surname = 'T’esting',
            $systemStatus = 'true',
            $town = null,
            $type = null,
            $uId = '700000001234');
    }
    public static function donorFixture(): SiriusPerson
    {
        return new SiriusPerson([
            'uId'       => '700000001111',
            'dob'       => '1975-10-05',
            'firstname' => 'Donor',
            'surname'   => 'Person',
            'addresses' => [
                [
                    'postcode' => 'PY1 3Kd',
                ],
            ],
        ]);
    }

}
