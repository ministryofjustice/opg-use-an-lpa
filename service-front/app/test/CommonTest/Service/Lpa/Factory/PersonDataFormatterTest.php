<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Factory;

use Common\Service\Lpa\Factory\PersonDataFormatter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class PersonDataFormatterTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|PersonDataFormatter $personDataFormatter;

    public function setUp(): void
    {
        $this->personDataFormatter = new PersonDataFormatter();
    }

    public function test_it_hydrates_person_data(): void
    {
        $combinedFormat = json_decode(file_get_contents(__DIR__ . '../../../../../fixtures/combined_lpa.json'), true);

        $result = ($this->personDataFormatter)($combinedFormat['donor']);

        $this->assertEquals('Rachel', $result->getFirstname());
        $this->assertEquals('DN37 5SH', $result->getPostcode());
        $this->assertEquals('81 Front Street', $result->getAddressLine1());
    }
}
