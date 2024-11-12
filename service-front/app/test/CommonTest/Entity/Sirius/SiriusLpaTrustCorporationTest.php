<?php

declare(strict_types=1);

namespace CommonTest\Entity\Sirius;

use Common\Entity\Sirius\SiriusLpaTrustCorporations;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SiriusLpaTrustCorporationTest extends TestCase
{
    #[Test]
    public function uses_name_as_company_name()
    {
        $this->assertEquals(
            'name',
            (new SiriusLpaTrustCorporations(
                addressLine1: '',
                addressLine2: '',
                addressLine3: '',
                country:      '',
                county:       '',
                dob:          new DateTimeImmutable(),
                email:        '',
                firstname:    '',
                firstnames:   '',
                name:         'name',
                otherNames:   '',
                postcode:     '',
                surname:      '',
                systemStatus: '',
                town:         '',
                type:         '',
                uId:          ''
            ))->getCompanyName()
        );
    }
}
