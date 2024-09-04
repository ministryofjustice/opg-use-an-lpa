<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\CastSingleDonor;
use App\Entity\LpaStore\LpaStoreDonor;
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
            'uId'         => '700000000971',
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
            null,
            '74 Cloob Close',
            null,
            null,
            'GB',
            null,
            null,
            'Mahhhhhhhhhh',
            null,
            new DateTimeImmutable('1970-01-24 00:00:00.000000'),
            'nobody@not.a.real.domain',
            null,
            'Feeg',
            'Bundlaaaa',
            null,
            null,
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
