<?php

declare(strict_types=1);

namespace AppTest\DataAccess\Repository\Response;

use App\DataAccess\Repository\Response\Lpa;
use DateTime;
use PHPUnit\Framework\TestCase;

class LpaTest extends TestCase
{
    /** @test */
    public function can_get_data_array_and_time(): void
    {
        $testArray = [
            'test' => true,
        ];

        $testDateTime = new DateTime();

        $lpa = new Lpa($testArray, $testDateTime);

        $this->assertEquals($testArray, $lpa->getData());
        $this->assertEquals($testDateTime, $lpa->getLookupTime());
    }

    /** @test */
    public function can_get_null_data_array_and_null_time(): void
    {
        $lpa = new Lpa([], new DateTime());

        $this->assertEmpty($lpa->getData());
        $this->assertInstanceOf(DateTime::class, $lpa->getLookupTime());
    }
}
