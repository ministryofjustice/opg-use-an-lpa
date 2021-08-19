<?php

namespace CommonTest\Filter;

use Common\Filter\ToDateTime;
use PHPUnit\Framework\TestCase;
use DateTime;

class ToDateTimeTest extends TestCase
{
    private ToDateTime $filter;

    public function setUp()
    {
        $this->filter = new ToDateTime();
    }

    public function testToDateTimeConversion()
    {
        $date = '1948-11-01 00:00:00';
        $expectedDate = new DateTime($date);

        $convertedDate = $this->filter->filter($date);
        $this->assertInstanceOf(DateTime::class, $convertedDate);
        $this->assertEquals($expectedDate, $convertedDate);
    }

    public function testToDateTimeConversionWhenDatePassedIsNotString()
    {
        $date = 1948 - 11 - 01;
        $expectedDate = new DateTime($date);

        $convertedDate = $this->filter->filter($date);
        $this->assertNotInstanceOf(DateTime::class, $convertedDate);
        $this->assertNotEquals($expectedDate, $convertedDate);
        $this->assertEquals($date, $convertedDate);
    }
}
