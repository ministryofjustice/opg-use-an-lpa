<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\Casters\ExtractAddressLine3FromSiriusLpa;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CanExtractAddressLine3FromSiriusLpaTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private ExtractAddressLine3FromSiriusLpa $extractAddressLine3FromSiriusLpa;

    public function setUp(): void
    {
        $this->mockHydrator                     = $this->createMock(ObjectMapper::class);
        $this->extractAddressLine3FromSiriusLpa = new ExtractAddressLine3FromSiriusLpa();
    }

    #[Test]
    public function can_extract_address_three_from_sirius(): void
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

        $expectedAddressThree = 'Street 3';

        $result = $this->extractAddressLine3FromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals($expectedAddressThree, $result);
    }

    #[Test]
    public function cannot_extract_address_three_from_sirius(): void
    {
        $addresses = [
            [
                'addressLine1' => '81 Front Street',
                'addressLine2' => 'LACEBY',
                'country'      => 'GB',
                'county'       => 'London',
                'postcode'     => 'DN37 5SH',
                'town'         => 'Town',
                'type'         => 'Primary',
            ],
        ];

        $result = $this->extractAddressLine3FromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals(null, $result);
    }
}
