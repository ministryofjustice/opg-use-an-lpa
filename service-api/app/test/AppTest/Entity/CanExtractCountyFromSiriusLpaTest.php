<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\Casters\ExtractCountyFromSiriusLpa;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CanExtractCountyFromSiriusLpaTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private ExtractCountyFromSiriusLpa $extractCountyFromSiriusLpa;

    public function setUp(): void
    {
        $this->mockHydrator               = $this->createMock(ObjectMapper::class);
        $this->extractCountyFromSiriusLpa = new ExtractCountyFromSiriusLpa();
    }

    #[Test]
    public function can_extract_county_from_sirius(): void
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

        $expectedCounty = 'London';

        $result = $this->extractCountyFromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals($expectedCounty, $result);
    }

    #[Test]
    public function cannot_extract_county_from_sirius(): void
    {
        $addresses = [
            [
                'addressLine1' => '81 Front Street',
                'addressLine2' => 'LACEBY',
                'addressLine3' => 'Street 3',
                'country'      => 'GB',
                'postcode'     => 'DN37 5SH',
                'town'         => 'Town',
                'type'         => 'Primary',
            ],
        ];

        $result = $this->extractCountyFromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals(null, $result);
    }
}
