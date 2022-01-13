<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\Entity\Address;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\View\Twig\LpaExtension;
use DateTime;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class LpaExtensionTest extends TestCase
{
    /** @test */
    public function it_returns_an_array_of_exported_twig_functions()
    {
        $extension = new LpaExtension();

        $functions = $extension->getFunctions();

        $this->assertTrue(is_array($functions));

        $expectedFunctions = [
            'actor_address'                   => 'actorAddress',
            'actor_name'                      => 'actorName',
            'lpa_date'                        => 'lpaDate',
            'code_date'                       => 'formatDate',
            'days_remaining_to_expiry'        => 'daysRemaining',
            'check_if_code_has_expired'       => 'hasCodeExpired',
            'add_hyphen_to_viewer_code'       => 'formatViewerCode',
            'check_if_code_is_cancelled'      => 'isCodeCancelled',
            'is_lpa_cancelled'                => 'isLpaCancelled',
            'donor_name_with_dob_removed'     => 'donorNameWithDobRemoved',
            'is_donor_signature_date_too_old' => 'isDonorSignatureDateOld',
        ];
        $this->assertEquals(count($expectedFunctions), count($functions));

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
        if (isset($addressLines['addressLine1'])) {
            $address->setAddressLine1($addressLines['addressLine1']);
        }
        if (isset($addressLines['addressLine2'])) {
            $address->setAddressLine2($addressLines['addressLine2']);
        }
        if (isset($addressLines['addressLine3'])) {
            $address->setAddressLine3($addressLines['addressLine3']);
        }
        if (isset($addressLines['town'])) {
            $address->setTown($addressLines['town']);
        }
        if (isset($addressLines['county'])) {
            $address->setCounty($addressLines['county']);
        }
        if (isset($addressLines['postcode'])) {
            $address->setPostcode($addressLines['postcode']);
        }

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
            [
                [],
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
        if (isset($nameLines['salutation'])) {
            $actor->setSalutation($nameLines['salutation']);
        }
        if (isset($nameLines['firstname'])) {
            $actor->setFirstname($nameLines['firstname']);
        }
        if (isset($nameLines['middlenames'])) {
            $actor->setMiddlenames($nameLines['middlenames']);
        }
        if (isset($nameLines['surname'])) {
            $actor->setSurname($nameLines['surname']);
        }

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
    public function it_creates_a_correctly_formatted_string_from_an_iso_date($date, $locale, $expected)
    {
        $extension = new LpaExtension();

        // retain the current locale
        $originalLocale = \Locale::getDefault();
        \Locale::setDefault($locale);

        $dateString = $extension->lpaDate($date);

        // restore the locale setting
        \Locale::setDefault($originalLocale);

        $this->assertEquals($expected, $dateString);
    }

    public function lpaDateDataProvider()
    {
        return [
            [
                '1948-02-17',
                'en_GB',
                '17 February 1948',
            ],
            [
                '1948-02-17',
                'cy_GB',
                '17 Chwefror 1948',
            ],
            [
                'today',
                'en_GB',
                (new DateTime('now'))->format('j F Y')
            ],
            [
                'not-a-date',
                'en_GB',
                '',
            ],
            [
                null,
                'en_GB',
                '',
            ]
        ];
    }

    /**
     * @test
     * @dataProvider codeDateDataProvider
     */
    public function it_creates_a_correctly_formatted_string_from_an_iso_date_for_check_codes($date, $locale, $expected)
    {
        $extension = new LpaExtension();

        // retain the current locale
        $originalLocale = \Locale::getDefault();
        \Locale::setDefault($locale);

        $dateString = $extension->formatDate($date);

        // restore the locale setting
        \Locale::setDefault($originalLocale);

        $this->assertEquals($expected, $dateString);
    }

    public function codeDateDataProvider()
    {
        return [
            [
                '2019-11-01T23:59:59+00:00',
                'en_GB',
                '1 November 2019',
            ],
            [
                '1972-03-22T23:59:59+00:00',
                'cy_GB',
                '22 Mawrth 1972',
            ],
            [
                'not-a-date',
                'en_GB',
                '',
            ],
            [
                null,
                'en_GB',
                '',
            ]
        ];
    }

    /**
     * @test
     * @dataProvider cancelledDateProvider
     */
    public function it_checks_if_a_code_is_cancelled($shareCodeArray, $expected)
    {

        $extension = new LpaExtension();

        $status = $extension->isCodeCancelled($shareCodeArray);

        $this->assertEquals($expected, $status);
    }

    public function cancelledDateProvider()
    {
        $shareCodeWithCancelledStatus = [
            'SiriusUid'        => '1234',
            'Added'            => '2021-01-05 12:34:56',
            'Expires'          => '2022-01-05 12:34:56',
            'Cancelled'        => '2022-01-06 12:34:56',
            'UserLpaActor'     => '111',
            'Organisation'     => 'TestOrg',
            'ViewerCode'       => 'XYZ321ABC987'
        ];
        $shareCodeWithoutCancelledStatus = [
            'SiriusUid'        => '1234',
            'Added'            => '2021-01-05 12:34:56',
            'Expires'          => '2022-01-07 12:34:56',
            'UserLpaActor'     => '111',
            'Organisation'     => 'TestOrg',
            'ViewerCode'       => 'XYZ321ABC987'
        ];
        return [
            [
                $shareCodeWithoutCancelledStatus,
                false,
            ],
            [
                $shareCodeWithCancelledStatus,
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider expiryDateProvider
     */
    public function it_checks_if_a_code_has_expired($expiryDate, $expected)
    {

        $extension = new LpaExtension();

        $status = $extension->hasCodeExpired($expiryDate);

        $this->assertEquals($expected, $status);
    }

    public function expiryDateProvider()
    {
        $future = (new DateTime('+1 week'))->format('Y-m-d');
        $past = (new DateTime('-1 week'))->format('Y-m-d');
        $endOfToday = (new DateTime('now'))->setTime(23, 59, 59)->format('Y-m-d');

        return [
            [
                $future,
                false,
            ],
            [
                $past,
                true,
            ],
            [
                $endOfToday,
                true,
            ],
            [
                '',
                null,
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

    /** @test */
    public function it_returns_an_hyphenated_viewer_code()
    {
        $extension = new LpaExtension();

        $viewerCode = $extension->formatViewerCode('111122223333');

        $this->assertEquals('V - 1111 - 2222 - 3333', $viewerCode);
    }

     /** @test */
    public function it_checks_if_an_LPA_is_cancelled()
    {
        $extension = new LpaExtension();
        $lpa = new Lpa();

        $lpa->setCancellationDate(new DateTime('-1 days'));
        $lpa->setStatus('Cancelled');
        $status = $extension->isLPACancelled($lpa);

        $this->assertEquals(true, $status);
    }

    /** @test */
    public function it_checks_if_an_LPA_is_not_cancelled()
    {
        $extension = new LpaExtension();
        $lpa = new Lpa();

        $lpa->setStatus('Registered');
        $status = $extension->isLPACancelled($lpa);

        $this->assertEquals(false, $status);
    }

    /** @test */
    public function it_checks_if_an_LPA_is_revoked()
    {
        $extension = new LpaExtension();
        $lpa = new Lpa();

        $lpa->setStatus('Revoked');
        $status = $extension->isLPACancelled($lpa);

        $this->assertEquals(true, $status);
    }

    /** @test */
    public function it_returns_donor_name_from_donor_nameDob_string()
    {
        $extension = new LpaExtension();

        $donorNameWithDob = "Harry Potter 1980-07-31";
        $donorName = $extension->donorNameWithDobRemoved($donorNameWithDob);

        $this->assertEquals('Harry Potter', $donorName);
    }

    /** @test */
    public function it_checks_if_an_lpa_donor_signature_is_old_for_i_and_p(): void
    {
        $extension = new LpaExtension();
        $lpa = new Lpa();

        $lpa->setLpaDonorSignatureDate(new DateTime('2015-01-01'));
        $status = $extension->isDonorSignatureDateOld($lpa);

        $this->assertEquals(true, $status);

        $lpa->setLpaDonorSignatureDate(new DateTime('2016-01-02'));
        $status = $extension->isDonorSignatureDateOld($lpa);

        $this->assertEquals(false, $status);
    }
}
