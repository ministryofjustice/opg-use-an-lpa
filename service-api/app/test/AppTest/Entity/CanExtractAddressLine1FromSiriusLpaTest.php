<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\Casters\ExtractAddressLine1FromSiriusLpa;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CanExtractAddressLine1FromSiriusLpaTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private ExtractAddressLine1FromSiriusLpa $extractAddressLine1FromSiriusLpa;

    public function setUp(): void
    {
        $this->mockHydrator                     = $this->createMock(ObjectMapper::class);
        $this->extractAddressLine1FromSiriusLpa = new ExtractAddressLine1FromSiriusLpa();
    }

    #[Test]
    public function can_extract_address_one_from_sirius(): void
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

        $expectedAddressOne = '81 Front Street';

        $result = $this->extractAddressLine1FromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals($expectedAddressOne, $result);
    }

    #[Test]
    public function cannot_extract_address_one_from_sirius(): void
    {
        $addresses = [
            [
                'addressLine2' => 'LACEBY',
                'addressLine3' => 'Street 3',
                'country'      => 'GB',
                'county'       => 'London',
                'postcode'     => 'DN37 5SH',
                'town'         => 'Town',
                'type'         => 'Primary',
            ],
        ];

        $result = $this->extractAddressLine1FromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals(null, $result);
    }
}
