<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\ExtractCountryFromLpaStore;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExtractCountryFromLpaStoreTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private ExtractCountryFromLpaStore $extractCountryFromLpaStore;

    public function setUp(): void
    {
        $this->mockHydrator               = $this->createMock(ObjectMapper::class);
        $this->extractCountryFromLpaStore = new ExtractCountryFromLpaStore();
    }

    #[Test]
    public function can_extract_country_from_datastore(): void
    {
        $address = [
            'line1'   => '74 Cloob Close',
            'town'    => 'Mahhhhhhhhhh',
            'country' => 'GB',
        ];

        $expectedCountry = 'GB';

        $result = $this->extractCountryFromLpaStore->cast($address, $this->mockHydrator);

        $this->assertEquals($expectedCountry, $result);
    }

    #[Test]
    public function cannot_extract_country_from_datastore(): void
    {
        $address = [
            'line1' => '74 Cloob Close',
            'town'  => 'Mahhhhhhhhhh',
        ];

        $result = $this->extractCountryFromLpaStore->cast($address, $this->mockHydrator);

        $this->assertEquals(null, $result);
    }
}
