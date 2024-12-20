<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\CastSingleDonor;
use App\Entity\LpaStore\LpaStoreDonor;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CastSingleDonorTest extends TestCase
{
    private CastSingleDonor $castSingleDonor;

    public function setUp(): void
    {
        $this->mockHydrator    = $this->createMock(ObjectMapper::class);
        $this->castSingleDonor = new CastSingleDonor();
    }

    #[Test]
    public function can_cast_single_donor(): void
    {
        $donor = [
            'uid'         => 'eda719db-8880-4dda-8c5d-bb9ea12c236f',
            'firstNames'  => 'Feeg',
            'surname'     => 'Gilson',
            'lastName'    => 'Bundlaaaa',
            'email'       => 'nobody@not.a.real.domain',
            'middlenames' => 'Suzanne',
            'dateOfBirth' => '1970-01-24',
            'address'     => [
                'line1'   => '74 Cloob Close',
                'town'    => 'Mahhhhhhhhhh',
                'country' => 'GB',
            ],
        ];

        $expectedDatastoreDonor = new LpaStoreDonor(
            addressLine1: '74 Cloob Close',
            addressLine2: null,
            addressLine3: null,
            country:      'GB',
            county:       null,
            dob:          new DateTimeImmutable('1970-01-24 00:00:00.000000'),
            email:        'nobody@not.a.real.domain',
            firstnames:   'Feeg',
            name:         null,
            postcode:     null,
            surname:      'Bundlaaaa',
            systemStatus: null,
            town:         'Mahhhhhhhhhh',
            type:         null,
            uId:          'eda719db-8880-4dda-8c5d-bb9ea12c236f',
        );

        $mapper = new ObjectMapperUsingReflection(
            new DefinitionProvider(
                keyFormatter: new KeyFormatterWithoutConversion(),
            ),
        );

        $result = $this->castSingleDonor->cast($donor, $mapper);
        $this->assertEquals($expectedDatastoreDonor, $result);
    }
}
