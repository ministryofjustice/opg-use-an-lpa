<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\Casters\CastSiriusDonor;
use App\Entity\Sirius\SiriusLpaDonor;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

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
            'uId'          => '700000000799',
            'name'         => null,
            'dob'          => '1948-11-01',
            'email'        => 'RachelSanderson@opgtest.com',
            'firstname'    => 'Rachel',
            'firstnames'   => null,
            'surname'      => 'Sanderson',
            'otherNames'   => null,
            'systemStatus' => null,
            'addresses'    => [
                [
                    'addressLine1' => '81 Front Street',
                    'addressLine2' => 'LACEBY',
                    'addressLine3' => 'Street 3',
                    'country'      => 'GB',
                    'county'       => 'London',
                    'postcode'     => 'DN37 5SH',
                    'town'         => 'Town',
                    'type'         => 'Primary',
                ],
            ],
            'linked'       => [
                [
                    'id'  => 7,
                    'uId' => '700000000799',
                ],
            ],
        ];

        $expectedSiriusDonor = new SiriusLpaDonor(
            '81 Front Street',
            'LACEBY',
            'Street 3',
            'GB',
            'London',
            new DateTimeImmutable('1948-11-01 00:00:00.000000'),
            'RachelSanderson@opgtest.com',
            'Rachel',
            null,
            [
                [
                    'id'  => 7,
                    'uId' => '700000000799',
                ],
            ],
            null,
            null,
            'DN37 5SH',
            'Sanderson',
            null,
            'Town',
            'Primary',
            '700000000799',
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
