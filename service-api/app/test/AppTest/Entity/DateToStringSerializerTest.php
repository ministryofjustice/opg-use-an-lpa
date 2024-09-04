<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\DateToStringSerializer;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class DateToStringSerializerTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private DateToStringSerializer $dateToStringSerializer;

    public function setUp(): void
    {
        $this->mockHydrator           = $this->createMock(ObjectMapper::class);
        $this->dateToStringSerializer = new DateToStringSerializer();
    }

    #[Test]
    public function can_date_to_string_serialised(): void
    {
        $date = new DateTimeImmutable('22-12-1997');

        $expecteDate = '1997-12-22T00:00:00+00:00';

        $result = $this->dateToStringSerializer->serialize($date, $this->mockHydrator);

        $this->assertEquals($expecteDate, $result);
    }

    #[Test]
    public function cannot_convert_date_to_string(): void
    {
        $date = '22-12-1997';

        $expecteDate = '22-12-1997';

        $result = $this->dateToStringSerializer->serialize($date, $this->mockHydrator);

        $this->assertEquals($expecteDate, $result);
    }
}
