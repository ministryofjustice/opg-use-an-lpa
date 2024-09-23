<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\Casters\ExtractCountryFromSiriusLpa;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CanExtractCountryFromSiriusLpaTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private ExtractCountryFromSiriusLpa $extractCountryFromSiriusLpa;

    public function setUp(): void
    {
        $this->mockHydrator                = $this->createMock(ObjectMapper::class);
        $this->extractCountryFromSiriusLpa = new ExtractCountryFromSiriusLpa();
    }

    #[Test]
    public function can_extract_country_from_sirius(): void
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

        $expectedCountry = 'GB';

        $result = $this->extractCountryFromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals($expectedCountry, $result);
    }

    #[Test]
    public function cannot_extract_country_from_sirius(): void
    {
        $addresses = [
            [
                'addressLine1' => '81 Front Street',
                'addressLine2' => 'LACEBY',
                'county'       => 'London',
                'postcode'     => 'DN37 5SH',
                'town'         => 'Town',
                'type'         => 'Primary',
            ],
        ];

        $result = $this->extractCountryFromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals(null, $result);
    }
}
