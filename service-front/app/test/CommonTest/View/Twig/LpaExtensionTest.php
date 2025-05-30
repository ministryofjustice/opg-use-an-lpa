<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\Entity\Person;
use Common\Service\Lpa\Factory\LpaDataFormatter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Common\Entity\Address;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\View\Twig\LpaExtension;
use DateTime;
use Locale;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class LpaExtensionTest extends TestCase
{
    private LpaDataFormatter $lpaDataFormatter;

    public function setUp(): void
    {
        $this->lpaDataFormatter = new LpaDataFormatter();
    }

    #[Test]
    public function it_returns_an_array_of_exported_twig_functions(): void
    {
        $extension = new LpaExtension();

        $functions = $extension->getFunctions();

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
            'is_sirius_lpa'                   => 'isSiriusLpa',
            'is_online_channel'               => 'isOnlineChannel',
        ];
        $this->assertEquals(count($expectedFunctions), count($functions));

        //  Check each function
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertContains($function->getName(), array_keys($expectedFunctions));

            $functionCallable = $function->getCallable();
            $this->assertInstanceOf(LpaExtension::class, $functionCallable[0]);
            $this->assertEquals($expectedFunctions[$function->getName()], $functionCallable[1]);
        }
    }

    #[DataProvider('addressDataProvider')]
    #[Test]
    public function it_concatenates_an_address_array_into_a_comma_separated_string($addressLines, $expected): void
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
        if (isset($addressLines['country'])) {
            $address->setCountry($addressLines['country']);
        }

        $actor = new CaseActor();
        $actor->setAddresses([$address]);

        $addressString = $extension->actorAddress($actor);

        $this->assertEquals($expected, $addressString);
    }

    #[Test]
    public function it_concatenates_an_address_array_into_a_comma_separated_string_for_combined_format(): void
    {
        $extension = new LpaExtension();

        $actor = new Person(
            addressLine1: 'Street 1',
            addressLine2: 'Street 2',
            addressLine3: 'Street 3',
            country: 'Country',
            county: 'County',
            dob: new DateTimeImmutable('22-12-1997'),
            email: 'email@email.com',
            firstnames: 'Jonathan',
            name: 'name',
            otherNames: 'Maverick',
            postcode: 'ABC 123',
            surname: 'Doe',
            systemStatus: 'true',
            town: 'Town',
            uId: '700000012345',
        );

        $expected      = 'Street 1, Street 2, Street 3, Town, County, ABC 123, Country';
        $addressString = $extension->actorAddress($actor);

        $this->assertEquals($expected, $addressString);
    }

    #[Test]
    public function access_address_key_values(): void
    {
        $address = new Address();
        $address->setId(1);
        $address->setType('Primary');

        $this->assertEquals(1, $address->getId());
        $this->assertEquals('Primary', $address->getType());
    }

    public static function addressDataProvider()
    {
        return [
            [
                [
                    'id'           => 1,
                    'addressLine1' => 'Some House',
                    'addressLine2' => 'Some Place',
                    'addressLine3' => 'Somewhere',
                    'town'         => 'Some Town',
                    'county'       => 'Some County',
                    'postcode'     => 'AB1 2CD',
                    'country'      => 'Some country',
                    'type'         => 'Primary',
                ],
                'Some House, Some Place, Somewhere, Some Town, Some County, AB1 2CD, Some country',
            ],
            [
                [
                    'addressLine1' => 'Some House1',
                    'addressLine2' => 'Some Place2',
                    'addressLine3' => 'Somewhere3',
                    'town'         => 'Some Town4',
                    'county'       => 'Some County5',
                    'postcode'     => 'AB1 2CQ',
                    'country'      => '',
                ],
                'Some House1, Some Place2, Somewhere3, Some Town4, Some County5, AB1 2CQ',
            ],
            [
                [
                    'addressLine1' => 'Some House',
                    'addressLine3' => 'Somewhere',
                    'town'         => 'Some Town',
                    'county'       => 'Some County',
                    'postcode'     => 'AB1 2CD',
                    'country'      => 'Some country',
                ],
                'Some House, Somewhere, Some Town, Some County, AB1 2CD, Some country',
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
                'Some House, Somewhere, Some Town, Some County, AB1 2CD',
            ],
            [
                null,
                '',
            ],
            [
                [],
                '',
            ],
        ];
    }

    #[DataProvider('nameDataProvider')]
    #[Test]
    public function it_concatenates_name_parts_into_a_single_string($nameLines, $expected): void
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

    public static function nameDataProvider()
    {
        return [
            [
                [
                    'salutation' => 'Mr',
                    'firstname'  => 'Jack',
                    'surname'    => 'Allen',
                ],
                'Mr Jack Allen',
            ],
            [
                [
                    'salutation'  => 'Mr',
                    'firstname'   => 'Jack',
                    'middlenames' => 'Oliver',
                    'surname'     => 'Allen',
                ],
                'Mr Jack Oliver Allen',
            ],
            [
                [
                    'salutation' => 'Mrs',
                    'firstname'  => 'Someone',
                    'surname'    => 'Taylor',
                ],
                'Mrs Someone Taylor',
            ],
            [
                [],
                '',
            ],
        ];
    }

    #[DataProvider('lpaDateDataProvider')]
    #[Test]
    public function it_creates_a_correctly_formatted_string_from_an_iso_date($date, $locale, $expected): void
    {
        $extension = new LpaExtension();

        // retain the current locale
        $originalLocale = Locale::getDefault();
        Locale::setDefault($locale);

        $dateString = $extension->lpaDate($date);

        // restore the locale setting
        Locale::setDefault($originalLocale);

        $this->assertEquals($expected, $dateString);
    }

    public static function lpaDateDataProvider()
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
                (new DateTime('now'))->format('j F Y'),
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
            ],
        ];
    }

    #[DataProvider('codeDateDataProvider')]
    #[Test]
    public function it_creates_a_correctly_formatted_string_from_an_iso_date_for_check_codes($date, $locale, $expected): void
    {
        $extension = new LpaExtension();

        // retain the current locale
        $originalLocale = Locale::getDefault();
        Locale::setDefault($locale);

        $dateString = $extension->formatDate($date);

        // restore the locale setting
        Locale::setDefault($originalLocale);

        $this->assertEquals($expected, $dateString);
    }

    public static function codeDateDataProvider()
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
            ],
        ];
    }

    #[DataProvider('cancelledDateProvider')]
    #[Test]
    public function it_checks_if_a_code_is_cancelled($shareCodeArray, $expected): void
    {

        $extension = new LpaExtension();

        $status = $extension->isCodeCancelled($shareCodeArray);

        $this->assertEquals($expected, $status);
    }

    public static function cancelledDateProvider()
    {
        $shareCodeWithCancelledStatus    = [
            'SiriusUid'    => '1234',
            'Added'        => '2021-01-05 12:34:56',
            'Expires'      => '2022-01-05 12:34:56',
            'Cancelled'    => '2022-01-06 12:34:56',
            'UserLpaActor' => '111',
            'Organisation' => 'TestOrg',
            'ViewerCode'   => 'XYZ321ABC987',
        ];
        $shareCodeWithoutCancelledStatus = [
            'SiriusUid'    => '1234',
            'Added'        => '2021-01-05 12:34:56',
            'Expires'      => '2022-01-07 12:34:56',
            'UserLpaActor' => '111',
            'Organisation' => 'TestOrg',
            'ViewerCode'   => 'XYZ321ABC987',
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

    #[DataProvider('expiryDateProvider')]
    #[Test]
    public function it_checks_if_a_code_has_expired($expiryDate, $expected): void
    {

        $extension = new LpaExtension();

        $status = $extension->hasCodeExpired($expiryDate);

        $this->assertEquals($expected, $status);
    }

    public static function expiryDateProvider()
    {
        $future     = (new DateTime('+1 week'))->format('Y-m-d');
        $past       = (new DateTime('-1 week'))->format('Y-m-d');
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
            ],
            [
                null,
                null,
            ],
        ];
    }

    #[Test]
    public function it_calculates_the_number_of_days_to_a_date_in_the_future_is_positive(): void
    {
        $extension = new LpaExtension();

        $date = new DateTime('+1 week');

        $days = $extension->daysRemaining($date->format('Y-m-d'));

        $this->assertGreaterThan(0, $days);
    }

    #[Test]
    public function it_returns_an_empty_string_if_expiry_date_is_null(): void
    {
        $extension = new LpaExtension();

        $days = $extension->daysRemaining(null);

        $this->assertEquals('', $days);
    }

    #[Test]
    public function it_returns_an_hyphenated_viewer_code(): void
    {
        $extension = new LpaExtension();

        $viewerCode = $extension->formatViewerCode('111122223333');

        $this->assertEquals('V - 1111 - 2222 - 3333', $viewerCode);
    }

     #[Test]
    public function it_checks_if_an_LPA_is_cancelled(): void
    {
        $extension = new LpaExtension();
        $lpa       = new Lpa();

        $lpa->setCancellationDate(new DateTime('-1 days'));
        $lpa->setStatus('Cancelled');
        $status = $extension->isLPACancelled($lpa);

        $this->assertEquals(true, $status);
    }

    #[Test]
    public function it_checks_if_an_LPA_is_not_cancelled(): void
    {
        $extension = new LpaExtension();
        $lpa       = new Lpa();

        $lpa->setStatus('Registered');
        $status = $extension->isLPACancelled($lpa);

        $this->assertEquals(false, $status);
    }

    #[Test]
    public function it_checks_if_an_LPA_is_revoked(): void
    {
        $extension = new LpaExtension();
        $lpa       = new Lpa();

        $lpa->setStatus('Revoked');
        $status = $extension->isLPACancelled($lpa);

        $this->assertEquals(true, $status);
    }

    #[Test]
    public function it_returns_donor_name_from_donor_nameDob_string(): void
    {
        $extension = new LpaExtension();

        $donorNameWithDob = 'Harry Potter 1980-07-31';
        $donorName        = $extension->donorNameWithDobRemoved($donorNameWithDob);

        $this->assertEquals('Harry Potter', $donorName);
    }

    #[Test]
    public function it_checks_if_an_lpa_donor_signature_is_old_for_i_and_p(): void
    {
        $extension = new LpaExtension();
        $lpa       = new Lpa();

        $lpa->setLpaDonorSignatureDate(new DateTime('2015-01-01'));
        $status = $extension->isDonorSignatureDateOld($lpa);

        $this->assertEquals(true, $status);

        $lpa->setLpaDonorSignatureDate(new DateTime('2016-01-02'));
        $status = $extension->isDonorSignatureDateOld($lpa);

        $this->assertEquals(false, $status);
    }

    #[Test]
    public function it_checks_if_an_lpa_donor_signature_is_old_for_i_and_p_for_combined_lpa(): void
    {
        $lpa         = json_decode(file_get_contents(__DIR__ . '../../../../../test/fixtures/test_lpa.json'), true);
        $combinedLpa = ($this->lpaDataFormatter)($lpa);

        $lpaDonorSignatureDate = $combinedLpa->getLpaDonorSignatureDate();

        $this->assertEquals(new DateTimeImmutable('2012-12-12'), $lpaDonorSignatureDate);
    }

    #[Test]
    public function it_checks_if_an_lpa_is_sirius_lpa_for_combined_lpa(): void
    {
        $extension   = new LpaExtension();
        $lpa         = json_decode(file_get_contents(__DIR__ . '../../../../../test/fixtures/test_lpa.json'), true);
        $combinedLpa = ($this->lpaDataFormatter)($lpa);

        $isSiriusLpa = $extension->isSiriusLpa($combinedLpa->getUId());

        $this->assertEquals(true, $isSiriusLpa);
    }

    #[Test]
    public function it_checks_if_an_LPA_is_online_channel_lpa_store(): void
    {
        $extension   = new LpaExtension();
        $lpa         = json_decode(file_get_contents(__DIR__ . '../../../../../test/fixtures/4UX3.json'), true);
        $combinedLpa = ($this->lpaDataFormatter)($lpa);

        $isOnlineChannel = $extension->isOnlineChannel($combinedLpa);

        $this->assertEquals(true, $isOnlineChannel);
    }
}
