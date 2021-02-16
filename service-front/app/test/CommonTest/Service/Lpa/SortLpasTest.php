<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Service\Lpa\SortLpas;

/**
 * Class SortLpasTest
 *
 * @coversDefaultClass \Common\Service\Lpa\SortLpas
 * @package CommonTest\Service\Lpa
 */
class SortLpasTest extends LpaFixtureTestCase
{
    /**
     * @test
     * @covers ::__invoke
     */
    public function it_sorts_lpas_by_donors_surname_forename(): void
    {
        $lpas = $this->lpaFixtureData();

        $sorter = new SortLpas();
        $orderedLpas = $sorter($lpas)->getArrayCopy();

        $resultOrder = [];
        foreach ($orderedLpas as $lpaKey => $lpaData) {
            $name = $lpaData->lpa->getDonor()->getSurname() . ', ' . $lpaData->lpa->getDonor()->getFirstname();
            array_push($resultOrder, $name);
        }
        $resultOrder = array_unique($resultOrder);

        $this->assertEquals('Johnson, Amy', array_shift($resultOrder));
        $this->assertEquals('Taylor, Gemma', array_shift($resultOrder));
        $this->assertEquals('Taylor, Sam', array_shift($resultOrder));
        $this->assertEquals('Williams, Daniel', array_shift($resultOrder));
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_sorts_lpas_by_donors_birthday(): void
    {
        $lpas = $this->lpaFixtureData();

        $sorter = new SortLpas();
        $orderedLpas = $sorter($lpas)->getArrayCopy();

        // pare the sorted fixture data back to what we're interested in.
        /** @var ArrayObject[] $orderedLpas */
        $orderedLpas = array_filter($orderedLpas, function (ArrayObject $lpaData) {
            return $lpaData->lpa->getDonor()->getSurname() . ', ' . $lpaData->lpa->getDonor()->getFirstname()
                === 'Taylor, Gemma';
        });

        $this->assertEquals(
            '1980-01-01',
            array_shift($orderedLpas)->lpa->getDonor()->getDob()->format('Y-m-d')
        ); // Gemma Taylor 1980-01-01

        $this->assertEquals(
            '1998-02-09',
            array_shift($orderedLpas)->lpa->getDonor()->getDob()->format('Y-m-d')
        ); // Gemma Taylor 1998-02-09
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_sorts_lpas_by_lpa_type(): void
    {
        $lpas = $this->lpaFixtureData();

        $sorter = new SortLpas();
        $orderedLpas = $sorter($lpas)->getArrayCopy();

        // pare the sorted fixture data back to what we're interested in.
        /** @var ArrayObject[] $orderedLpas */
        $orderedLpas = array_filter($orderedLpas, function (ArrayObject $lpaData) {
            return $lpaData->lpa->getDonor()->getSurname() . ', ' . $lpaData->lpa->getDonor()->getFirstname()
                === 'Williams, Daniel';
        });

        $this->assertEquals(
            'hw',
            array_shift($orderedLpas)->lpa->getCaseSubtype()
        ); // Daniel Williams 1980-01-01

        $this->assertEquals(
            'pfa',
            array_shift($orderedLpas)->lpa->getCaseSubtype()
        ); // Daniel Williams 1980-01-01

        $this->assertEquals(
            'pfa',
            array_shift($orderedLpas)->lpa->getCaseSubtype()
        ); // Daniel Williams 1980-01-01
    }

    /**
     * A full test of the expected order of LPA's returned from the sorter. Duplicates the work of each of the
     * individual tests above but is also more prone to breakage if the fixture data changes at all.
     *
     * @test
     * @covers ::__invoke
     */
    public function it_sorts_into_expected_order(): void
    {
        $lpas = $this->lpaFixtureData();

        $sorter = new SortLpas();
        $orderedLpas = $sorter($lpas)->getArrayCopy();

        $expectedOrder = [
            '0007-01-01-01-777777', // Amy Johnson 1980-01-01
            '0002-01-01-01-222222',
            '0008-01-01-01-888888', // Gemma Taylor 1980-01-01
            '0009-01-01-01-999999', // Gemma Taylor 1998-02-09
            '0004-01-01-01-444444', // Sam Taylor 1980-01-01
            '0003-01-01-01-333333',
            '0001-01-01-01-111111', // Daniel Williams 1980-01-01
            '0006-01-01-01-666666',
            '0005-01-01-01-555555'
        ];

        $this->assertEquals($expectedOrder, array_keys($orderedLpas));
    }
}
