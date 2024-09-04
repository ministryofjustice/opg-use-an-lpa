<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\ExtractAddressLine1FromLpaStore;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExtractAddressLine1FromLpaStoreTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private ExtractAddressLine1FromLpaStore $extractAddressLine1FromLpaStore;

    public function setUp(): void
    {
        $this->mockHydrator                    = $this->createMock(ObjectMapper::class);
        $this->extractAddressLine1FromLpaStore = new ExtractAddressLine1FromLpaStore();
    }

    #[Test]
    public function can_extract_address_one_from_datastore(): void
    {
        $address = [
            'line1'   => '74 Cloob Close',
            'town'    => 'Mahhhhhhhhhh',
            'country' => 'GB',
        ];

        $expectedAddressOne = '74 Cloob Close';

        $result = $this->extractAddressLine1FromLpaStore->cast($address, $this->mockHydrator);

        $this->assertEquals($expectedAddressOne, $result);
    }

    #[Test]
    public function cannot_extract_address_one_from_datastore(): void
    {
        $address = [
            'town'    => 'Mahhhhhhhhhh',
            'country' => 'GB',
        ];

        $result = $this->extractAddressLine1FromLpaStore->cast($address, $this->mockHydrator);

        $this->assertEquals(null, $result);
    }
}
