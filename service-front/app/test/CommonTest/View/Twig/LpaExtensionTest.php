<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\Entity\Address;
use Common\Entity\CaseActor;
use Common\View\Twig\LpaExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;
use DateTime;

class LpaExtensionTest extends TestCase
{
    /** @test */
    public function it_returns_an_array_of_exported_twig_functions()
    {
        $extension = new LpaExtension();

        $functions = $extension->getFunctions();

        $this->assertTrue(is_array($functions));
        $this->assertEquals(4, count($functions));

        $expectedFunctions = [
            'actor_address'             => 'actorAddress',
            'actor_name'                => 'actorName',
            'lpa_date'                  => 'lpaDate',
            'days_remaining_to_expiry'  => 'daysRemaining',
        ];

        //  Check each function
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            /** @var TwigFunction $function */
            $this->assertContains($function->getName(), array_keys($expectedFunctions));

            $functionCallable = $function->getCallable();
            $this->assertInstanceOf(LpaExtension::class, $functionCallable[0]);
            $this->assertEquals($expectedFunctions[$function->getName()], $functionCallable[1]);
        }
    }

    /**
     * @test
     * @dataProvider addressDataProvider
     */
    public function it_concatenates_an_address_array_into_a_comma_separated_string($addressLines, $expected)
    {
        $extension = new LpaExtension();

        $address = new Address();
        if (isset($addressLines['addressLine1'])) { $address->setAddressLine1($addressLines['addressLine1']); }
        if (isset($addressLines['addressLine2'])) { $address->setAddressLine2($addressLines['addressLine2']); }
        if (isset($addressLines['addressLine3'])) { $address->setAddressLine3($addressLines['addressLine3']); }
        if (isset($addressLines['town'])) { $address->setTown($addressLines['town']); }
        if (isset($addressLines['county'])) { $address->setCounty($addressLines['county']); }
        if (isset($addressLines['postcode'])) { $address->setPostcode($addressLines['postcode']); }

        $actor = new CaseActor();
        $actor->setAddresses([$address]);

        $addressString = $extension->actorAddress($actor);

        $this->assertEquals($expected, $addressString);
    }

    public function addressDataProvider()
    {
        return [
            [
                [
                    'addressLine1' => 'Some House',
                    'addressLine2' => 'Some Place',
                    'addressLine3' => 'Somewhere',
                    'town'         => 'Some Town',
                    'county'       => 'Some County',
                    'postcode'     => 'AB1 2CD',
                ],
                'Some House, Some Place, Somewhere, Some Town, Some County, AB1 2CD'
            ],
            [
                [
                    'addressLine1' => 'Some House1',
                    'addressLine2' => 'Some Place2',
                    'addressLine3' => 'Somewhere3',
                    'town'         => 'Some Town4',
                    'county'       => 'Some County5',
                    'postcode'     => 'AB1 2CQ',
                ],
                'Some House1, Some Place2, Somewhere3, Some Town4, Some County5, AB1 2CQ'
            ],
            [
                [
                    'addressLine1' => 'Some House',
                    'addressLine3' => 'Somewhere',
                    'town'         => 'Some Town',
                    'county'       => 'Some County',
                    'postcode'     => 'AB1 2CD',
                ],
                'Some House, Somewhere, Some Town, Some County, AB1 2CD'
            ],
            [
                [
                    'addressLine1' => 'Some House',
                    'addressLine3' => 'Somewhere',
                    'town'         => 'Some Town',
                    'county'       => 'Some County',
                    'postcode'     => 'AB1 2CD',
                    'ignoreField'  => 'This value won\'t show',
                ],
                'Some House, Somewhere, Some Town, Some County, AB1 2CD'
            ],
            [
                null,
                ''
            ],
        ];
    }

    /**
     * @test
     * @dataProvider nameDataProvider
     */
    public function it_concatenates_name_parts_into_a_single_string($nameLines, $expected)
    {
        $extension = new LpaExtension();

        $actor = new CaseActor();
        if (isset($nameLines['salutation'])) { $actor->setSalutation($nameLines['salutation']); }
        if (isset($nameLines['firstname'])) { $actor->setFirstname($nameLines['firstname']); }
        if (isset($nameLines['middlenames'])) { $actor->setMiddlenames($nameLines['middlenames']); }
        if (isset($nameLines['surname'])) { $actor->setSurname($nameLines['surname']); }

        $name = $extension->actorName($actor);

        $this->assertEquals($expected, $name);
    }

    public function nameDataProvider()
    {
        return [
            [
                [
                    'salutation' => 'Mr',
                    'firstname'  => 'Jack',
                    'surname'    => 'Allen',
                ],
                'Mr Jack Allen'
            ],
            [
                [
                    'salutation' => 'Mr',
                    'firstname'  => 'Jack',
                    'middlenames' => 'Oliver',
                    'surname'    => 'Allen',
                ],
                'Mr Jack Oliver Allen'
            ],
            [
                [
                    'salutation' => 'Mrs',
                    'firstname'  => 'Someone',
                    'surname'    => 'Taylor',
                ],
                'Mrs Someone Taylor'
            ],
            [
                [],
                ''
            ],
        ];
    }

    /**
     * @test
     * @dataProvider lpaDateDataProvider
     */
    public function it_creates_a_correctly_formatted_string_from_an_iso_date($date, $expected)
    {
        $extension = new LpaExtension();

        $name = $extension->lpaDate($date);

        $this->assertEquals($expected, $name);
    }

    public function lpaDateDataProvider()
    {
        return [
            [
                '1980-01-01',
                '1 January 1980',
            ],
            [
                '1948-02-17',
                '17 February 1948',
            ],
            [
                'not-a-date',
                '',
            ],
            [
                null,
                '',
            ]
        ];
    }

    /** @test */
    public function it_calculates_the_number_of_days_to_a_date_in_the_future_is_positive()
    {
        $extension = new LpaExtension();

        $date = new DateTime('+1 week');

        $days = $extension->daysRemaining($date->format('Y-m-d'));

        $this->assertGreaterThan(0, $days);
    }

    /** @test */
    public function it_returns_an_empty_string_if_expiry_date_is_null()
    {
        $extension = new LpaExtension();

        $days = $extension->daysRemaining(null);

        $this->assertEquals('', $days);
    }
}
