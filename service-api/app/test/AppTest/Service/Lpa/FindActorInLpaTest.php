<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\FindActorInLpa;
use App\Service\Lpa\GetAttorneyStatus;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class FindActorInLpaTest extends TestCase
{
    /** @var ObjectProphecy|GetAttorneyStatus  */
    private $getAttorneyStatusProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    public function setUp()
    {
        $this->getAttorneyStatusProphecy = $this->prophesize(GetAttorneyStatus::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    /**
     * @test
     * @dataProvider actorLookupDataProvider
     * @param ?array $expectedResponse
     * @param array  $userData
     */
    public function returns_actor_and_lpa_details_if_match_found_in_lookup(?array $expectedResponse, array $userData)
    {
        $lpa = [
            'uId' => '700000012345',
            'donor' => [
                'uId'       => '700000001111',
                'dob'       => '1975-10-05',
                'firstname' => 'Donor',
                'surname'   => 'Person',
                'addresses' => [
                    [
                        'postcode' => 'PY1 3Kd'
                    ]
                ]
            ],
            'attorneys' => [
                [
                    'uId'       => '700000002222',
                    'dob'       => '1977-11-21',
                    'firstname' => 'Attorneyone',
                    'surname'   => 'Person',
                    'addresses' => [
                        [
                            'postcode' => 'Gg1 2ff'
                        ]
                    ],
                    'systemStatus' => false, // inactive attorney
                ],
                [
                    'uId'       => '700000003333',
                    'dob'       => '1960-05-05',
                    'firstname' => '', // ghost attorney
                    'surname'   => '',
                    'addresses' => [
                        [
                            'postcode' => 'BB1 9ee'
                        ]
                    ],
                    'systemStatus' => true,
                ],
                [
                    'uId'       => '700000004444',
                    'dob'       => '1980-03-01',
                    'firstname' => 'Attorneythree',
                    'surname'   => 'Person',
                    'addresses' => [ // multiple addresses
                        [
                            'postcode' => 'Ab1 2Cd'
                        ],
                        [
                            'postcode' => 'Bc2 3Df'
                        ]
                    ],
                    'systemStatus' => true,
                ],
                [
                    'uId'       => '700000001234',
                    'dob'       => '1980-03-01',
                    'firstname' => 'Test',
                    'surname'   => 'Testing',
                    'addresses' => [
                        [
                            'postcode' => 'Ab1 2Cd'
                        ]
                    ],
                    'systemStatus' => true,
                ]
            ]
        ];

        $this->getAttorneyStatusProphecy
            ->__invoke([
                           'uId'       => '700000002222',
                           'dob'       => '1977-11-21',
                           'firstname' => 'Attorneyone',
                           'surname'   => 'Person',
                           'addresses' => [
                               [
                                   'postcode' => 'Gg1 2ff'
                               ]
                           ],
                           'systemStatus' => false, // inactive attorney
                       ])
            ->willReturn(2);

        $this->getAttorneyStatusProphecy
            ->__invoke([
                           'uId'       => '700000003333',
                           'dob'       => '1960-05-05',
                           'firstname' => '', // ghost attorney
                           'surname'   => '',
                           'addresses' => [
                               [
                                   'postcode' => 'BB1 9ee'
                               ]
                           ],
                           'systemStatus' => true,
                       ])
            ->willReturn(1);

        $this->getAttorneyStatusProphecy
            ->__invoke([
                           'uId'       => '700000004444',
                           'dob'       => '1980-03-01',
                           'firstname' => 'Attorneythree',
                           'surname'   => 'Person',
                           'addresses' => [ // multiple addresses
                               [
                                   'postcode' => 'Ab1 2Cd'
                               ],
                               [
                                   'postcode' => 'Bc2 3Df'
                               ]
                           ],
                           'systemStatus' => true,
                       ])
            ->willReturn(0);

        $this->getAttorneyStatusProphecy
            ->__invoke([
                           'uId'       => '700000001234',
                           'dob'       => '1980-03-01',
                           'firstname' => 'Test',
                           'surname'   => 'Testing',
                           'addresses' => [
                               [
                                   'postcode' => 'Ab1 2Cd'
                               ]
                           ],
                           'systemStatus' => true,
                       ])
            ->willReturn(0); // active attorney

        $sut = new FindActorInLpa(
            $this->getAttorneyStatusProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );

        $matchData = $sut($lpa, $userData);
        $this->assertEquals($expectedResponse, $matchData);
    }

    public function actorLookupDataProvider(): array
    {
        return [
            [
                [
                    'actor-id' => '700000001234', // successful match for attorney
                    'lpa-id'   => '700000012345'
                ],
                [
                    'dob'         => '1980-03-01',
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Testing',
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                [
                    'actor-id' => '700000001111', // successful match for donor
                    'lpa-id'   => '700000012345'
                ],
                [
                    'dob'         => '1975-10-05',
                    'first_names' => 'Donor',
                    'last_name'   => 'Person',
                    'postcode'    => 'PY1 3Kd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '1982-01-20', // dob will not match
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Testing',
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '1980-03-01',
                    'first_names' => 'Wrong', // firstname will not match
                    'last_name'   => 'Testing',
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '1980-03-01',
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Incorrect', // surname will not match
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '1980-03-01',
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Testing',
                    'postcode'    => 'WR0 NG1' // postcode will not match
                ],
            ],
            [
                null, // will not find a match as this attorney is inactive
                [
                    'dob'         => '1977-11-21',
                    'first_names' => 'Attorneyone',
                    'last_name'   => 'Person',
                    'postcode'    => 'Gg1 2ff'
                ],
            ],
            [
                null, // will not find a match as this attorney is a ghost
                [
                    'dob'         => '1960-05-05',
                    'first_names' => 'Attorneytwo',
                    'last_name'   => 'Person',
                    'postcode'    => 'BB1 9ee'
                ],
            ]
        ];
    }
}
