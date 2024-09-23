<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Sirius\Casters\ExtractTypeFromSiriusLpa;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CanExtractTypeFromSiriusLpaTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private ExtractTypeFromSiriusLpa $extractTypeFromSiriusLpa;

    public function setUp(): void
    {
        $this->mockHydrator             = $this->createMock(ObjectMapper::class);
        $this->extractTypeFromSiriusLpa = new ExtractTypeFromSiriusLpa();
    }

    #[Test]
    public function can_extract_type_from_sirius(): void
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

        $expectedType = 'Primary';

        $result = $this->extractTypeFromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals($expectedType, $result);
    }

    #[Test]
    public function cannot_extract_type_from_sirius(): void
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
            ],
        ];

        $result = $this->extractTypeFromSiriusLpa->cast($addresses, $this->mockHydrator);

        $this->assertEquals(null, $result);
    }
}
