<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\ExtractTownFromLpaStore;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExtractTownFromLpaStoreTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private ExtractTownFromLpaStore $extractTownFromLpaStore;

    public function setUp(): void
    {
        $this->mockHydrator            = $this->createMock(ObjectMapper::class);
        $this->extractTownFromLpaStore = new ExtractTownFromLpaStore();
    }

    #[Test]
    public function can_extract_town_from_datastore(): void
    {
        $address = [
            'line1'   => '74 Cloob Close',
            'town'    => 'Mahhhhhhhhhh',
            'country' => 'GB',
        ];

        $expectedTown = 'Mahhhhhhhhhh';

        $result = $this->extractTownFromLpaStore->cast($address, $this->mockHydrator);

        $this->assertEquals($expectedTown, $result);
    }

    #[Test]
    public function cannot_extract_town_from_datastore(): void
    {
        $address = [
            'line1'   => '74 Cloob Close',
            'country' => 'GB',
        ];

        $result = $this->extractTownFromLpaStore->cast($address, $this->mockHydrator);

        $this->assertEquals(null, $result);
    }
}