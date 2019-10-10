<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\LpaExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;
use DateTime;

class LpaExtensionTest extends TestCase
{
    public function testGetFunctions()
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
     * @dataProvider addressDataProvider
     */
    public function testActorAddress($addressLines, $expected)
    {
        $extension = new LpaExtension();

        //  Construct the actor data
        $addresses = [];

        if (!empty($addressLines)) {
            $addresses[] = $addressLines;
        }

        $address = $extension->actorAddress([
            'addresses' => $addresses,
        ]);

        $this->assertEquals($expected, $address);
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
     * @dataProvider nameDataProvider
     */
    public function testActorName($nameLines, $expected)
    {
        $extension = new LpaExtension();

        $name = $extension->actorName($nameLines);

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
                    'salutation' => 'Mrs',
                    'firstname'  => 'Someone',
                    'surname'    => 'Taylor',
                ],
                'Mrs Someone Taylor'
            ],
            [
                [
                    'salutation'  => 'Mrs',
                    'firstname'   => 'Someone',
                    'ignoreField' => 'This value won\'t show',
                    'surname'     => 'Taylor',
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
     * @dataProvider lpaDateDataProvider
     */
    public function testLpaDate($date, $expected)
    {
        $extension = new LpaExtension();

        $name = $extension->lpaDate($date);

        $this->assertEquals($expected, $name);
    }

    public function lpaDateDataProvider()
    {
        $today = new DateTime("today");
        $today = $today->format("j F Y");

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
                'today',
                $today,
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

    public function testDaysRemainingIsPositive()
    {
        $extension = new LpaExtension();

        $date = new DateTime('+1 week');

        $days = $extension->daysRemaining($date->format('Y-m-d'));

        $this->assertGreaterThan(0, $days);
    }

    public function testDaysRemainingIsNull()
    {
        $extension = new LpaExtension();

        $days = $extension->daysRemaining(null);

        $this->assertEquals('', $days);
    }
}
