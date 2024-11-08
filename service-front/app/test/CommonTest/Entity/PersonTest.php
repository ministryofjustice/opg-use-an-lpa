<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Entity\Address;
use CommonTest\Helper\EntityTestHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PersonTest extends TestCase
{
    #[Test]
    public function wraps_address_in_array_for_compatability()
    {
        $this->assertEquals(
            [(new Address())
                ->setAddressLine1('Address Line 1')
                ->setAddressLine2('Address Line 2')
                ->setAddressLine3('Address Line 3')
                ->setTown('Town')
                ->setPostcode('Postcode')
                ->setCounty('County')
                ->setCountry('Country')
            ],
            EntityTestHelper::MakePerson()->getAddresses()
        );
    }
}
