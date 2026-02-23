<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\LpaStore\LpaStoreAttorney;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaStoreAttorneyTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $sut = new LpaStoreAttorney(
            line1:        null,
            line2:        null,
            line3:        null,
            cannotMakeJointDecisions: null,
            country:      null,
            county:       null,
            dateOfBirth:  null,
            email:        null,
            firstNames:   'John',
            postcode:     null,
            lastName:     null,
            status:       null,
            town:         null,
            uId:          '700000000012',
        );

        $this->assertInstanceOf(LpaStoreAttorney::class, $sut);
        $this->assertEquals('John', $sut->getFirstname());
    }
}
