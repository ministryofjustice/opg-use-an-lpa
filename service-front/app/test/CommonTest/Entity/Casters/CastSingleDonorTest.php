<?php

declare(strict_types=1);

namespace CommonTest\Entity\Casters;

use Common\Entity\Casters\CastSingleDonor;
use Common\Entity\LpaStore\LpaStoreDonor;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

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
            '74 Cloob Close',
            null,
            null,
            'GB',
            null,
            new DateTimeImmutable('1970-01-24 00:00:00.000000'),
            'nobody@not.a.real.domain',
            null,
            'Feeg',
            null,
            null,
            null,
            'Bundlaaaa',
            null,
            'Mahhhhhhhhhh',
            null,
            'eda719db-8880-4dda-8c5d-bb9ea12c236f',
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
