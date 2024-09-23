<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\Casters\ExtractPostcodeFromSiriusLpa;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CanExtractPostcodeFromSiriusLpaTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private ExtractPostcodeFromSiriusLpa $extractPostcodeFromSiriusLpa;

    public function setUp(): void
    {
        $this->mockHydrator                 = $this->createMock(ObjectMapper::class);
        $this->extractPostcodeFromSiriusLpa = new ExtractPostcodeFromSiriusLpa();
    }

    #[Test]
    public function can_extract_postcode_from_sirius(): void
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

        $expectedPostcode = 'DN37 5SH';

        $result = $this->extractPostcodeFromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals($expectedPostcode, $result);
    }

    #[Test]
    public function cannot_extract_postcode_from_sirius(): void
    {
        $addresses = [
            [
                'addressLine1' => '81 Front Street',
                'addressLine2' => 'LACEBY',
                'addressLine3' => 'Street 3',
                'country'      => 'GB',
                'county'       => 'London',
                'town'         => 'Town',
                'type'         => 'Primary',
            ],
        ];

        $result = $this->extractPostcodeFromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals(null, $result);
    }
}
