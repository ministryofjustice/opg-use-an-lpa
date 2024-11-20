<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\Casters\CastSiriusDonor;
use App\Entity\Sirius\SiriusLpaDonor;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CanCastSiriusDonorTest extends TestCase
{
    private CastSiriusDonor $castSiriusDonor;

    public function setUp(): void
    {
        $this->mockHydrator    = $this->createMock(ObjectMapper::class);
        $this->castSiriusDonor = new CastSiriusDonor();
    }

    #[Test]
    public function can_cast_sirius_donor(): void
    {
        $donor = [
            'id'          => 7,
            'uId'         => '700000000799',
            'linked'      => [
                [
                    'id'  => 7,
                    'uId' => '700000000799',
                ],
            ],
            'dob'         => '1948-11-01',
            'email'       => 'RachelSanderson@opgtest.com',
            'salutation'  => 'Mr',
            'firstname'   => 'Rachel',
            'middlenames' => 'Emma',
            'surname'     => 'Sanderson',
            'addresses'   => [
                [
                    'id'           => 7,
                    'town'         => 'Town',
                    'county'       => '',
                    'postcode'     => 'DN37 5SH',
                    'country'      => '',
                    'type'         => 'Primary',
                    'addressLine1' => '81 Front Street',
                    'addressLine2' => 'LACEBY',
                    'addressLine3' => '',
                ],
            ],
            'companyName' => null,
        ];

        $expectedSiriusDonor = new SiriusLpaDonor(
            addressLine1: '81 Front Street',
            addressLine2: 'LACEBY',
            addressLine3: '',
            country:      '',
            county:       '',
            dob:          new DateTimeImmutable('1948-11-01T00:00:00Z'),
            email:        'RachelSanderson@opgtest.com',
            firstname:    'Rachel',
            id:           '7',
            linked:       [
                [
                    'id'  => 7,
                    'uId' => '700000000799',
                ],
            ],
            middlenames:  'Emma',
            otherNames:   null,
            postcode:     'DN37 5SH',
            surname:      'Sanderson',
            systemStatus: null,
            town:         'Town',
            type:         'Primary',
            uId:          '700000000799',
        );

        $mapper = new ObjectMapperUsingReflection(
            new DefinitionProvider(
                keyFormatter: new KeyFormatterWithoutConversion(),
            ),
        );

        $result = $this->castSiriusDonor->cast($donor, $mapper);
        $this->assertEquals($expectedSiriusDonor, $result);
    }
}
