<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\LpaStore\LpaStoreDonor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaStoreDonorTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $sut = new LpaStoreDonor(
            line1:             null,
            line2:             null,
            line3:             null,
            country:           null,
            county:            null,
            dateOfBirth:       null,
            email:             null,
            firstNames:        'John',
            otherNamesKnownBy: null,
            postcode:          null,
            lastName:          null,
            town:              null,
            uId:               '700000000012',
        );

        $this->assertInstanceOf(LpaStoreDonor::class, $sut);
        $this->assertEquals('John', $sut->getFirstname());
    }
}
