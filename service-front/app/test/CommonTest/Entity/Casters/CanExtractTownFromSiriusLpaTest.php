<?php

declare(strict_types=1);

namespace CommonTest\Entity\Casters;

use Common\Entity\Casters\ExtractTownFromSiriusLpa;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CanExtractTownFromSiriusLpaTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private ExtractTownFromSiriusLpa $extractTownFromSiriusLpa;

    public function setUp(): void
    {
        $this->mockHydrator             = $this->createMock(ObjectMapper::class);
        $this->extractTownFromSiriusLpa = new ExtractTownFromSiriusLpa();
    }

    #[Test]
    public function can_extract_town_from_sirius(): void
    {
        $addresses = [
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
        ];

        $expectedTown = 'Town';

        $result = $this->extractTownFromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals($expectedTown, $result);
    }

    #[Test]
    public function cannot_extract_town_from_sirius(): void
    {
        $addresses = [
            [
                'addressLine1' => '81 Front Street',
                'addressLine2' => 'LACEBY',
                'addressLine3' => 'Street 3',
                'country'      => 'GB',
                'county'       => 'London',
                'postcode'     => 'DN37 5SH',
                'type'         => 'Primary',
            ],
        ];

        $result = $this->extractTownFromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals(null, $result);
    }
}
