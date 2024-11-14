<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Entity\Sirius\SiriusLpaAttorney;
use Common\Entity\Sirius\SiriusLpaTrustCorporations;
use Common\Service\Lpa\Factory\LpaDataFormatter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CombinedLpaTest extends TestCase
{
    use ProphecyTrait;

    private LpaDataFormatter $lpaDataFormatter;

    public function setUp(): void
    {
        $this->lpaDataFormatter = new LpaDataFormatter();
    }

    #[Test]
    public function can_test_getters()
    {
        $lpa         = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'), true);
        $combinedLpa = ($this->lpaDataFormatter)($lpa);

        $expectedUid                    = '700000000047';
        $expectedApplicationHasGuidance = false;
        $expectedHasRestrictions        = false;
        $expectedStatus                 = 'Registered';
        $expectedAttorneys              = $this->getMockedAttorneys();
        $expectedTrustCorporations      = $this->getMockedTrustCorporations();

        $this->assertEquals($expectedUid, $combinedLpa->getUId());
        $this->assertEquals($expectedApplicationHasGuidance, $combinedLpa->getApplicationHasGuidance());
        $this->assertEquals($expectedHasRestrictions, $combinedLpa->getApplicationHasRestrictions());
        $this->assertEquals($expectedStatus, $combinedLpa->getStatus());
        $this->assertEquals($expectedAttorneys, $combinedLpa->getAttorneys());
        $this->assertEquals($expectedTrustCorporations, $combinedLpa->getTrustCorporations());
    }

    public function getMockedAttorneys(): array
    {
        return [
            new SiriusLpaAttorney(
                addressLine1 : '9 high street',
                addressLine2 : '',
                addressLine3 : '',
                country      : '',
                county       : '',
                dob          : new DateTimeImmutable('1990-05-04'),
                email        : '',
                firstname    : 'jean',
                firstnames   : null,
                name         : null,
                otherNames   : null,
                postcode     : 'DN37 5SH',
                surname      : 'sanderson',
                systemStatus : '1',
                town         : '',
                type         : 'Primary',
                uId          : '700000000815'
            ),
            new SiriusLpaAttorney(
                addressLine1       : '',
                addressLine2       : '',
                addressLine3       : '',
                country            : '',
                county             : '',
                dob                : new DateTimeImmutable('1975-10-05'),
                email              : 'XXXXX',
                firstname          : 'Ann',
                firstnames         : null,
                name               : null,
                otherNames         : null,
                postcode           : '',
                surname            : 'Summers',
                systemStatus       : '1',
                town               : '',
                type               : 'Primary',
                uId                : '7000-0000-0849'
            ),
        ];
    }

    public function getMockedTrustCorporations(): array
    {
        return [
            new SiriusLpaTrustCorporations(
                addressLine1 : 'Street 1',
                addressLine2 : 'Street 2',
                addressLine3 : 'Street 3',
                country      : 'GB',
                county       : 'County',
                dob          : null,
                email        : null,
                firstname    : 'trust',
                firstnames   : null,
                name         : 'trust corporation',
                otherNames   : null,
                postcode     : 'ABC 123',
                surname      : 'test',
                systemStatus : '1',
                town         : 'Town',
                type         : 'Primary',
                uId          : '7000-0015-1998',
            ),
        ];
    }
}
