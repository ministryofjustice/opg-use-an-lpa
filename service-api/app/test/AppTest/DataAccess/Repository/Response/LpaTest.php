<?php

declare(strict_types=1);

namespace AppTest\DataAccess\Repository\Response;

use DateTime;
use PHPUnit\Framework\TestCase;
use App\DataAccess\Repository\Response\Lpa;

class LpaTest extends TestCase
{
    /** @test */
    public function can_get_data_array_and_time()
    {
        $testArray = [
            'test' => true
        ];

        $testDateTime = new DateTime();

        $lpa = new Lpa($testArray, $testDateTime);

        $this->assertEquals($testArray, $lpa->getData());
        $this->assertEquals($testDateTime, $lpa->getLookupTime());
    }

    /** @test */
    public function can_get_null_data_array_and_null_time()
    {
        $lpa = new Lpa(null, null);

        $this->assertNull($lpa->getData());
        $this->assertNull($lpa->getLookupTime());
    }

}
