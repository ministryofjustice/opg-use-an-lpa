<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
use Common\Service\Features\FeatureEnabled;
use Exception;
use Common\Service\Lpa\Factory\LpaDataFormatter;

/**
 * Single action invokeable class that transforms incoming LPA data arrays from the API into ones containing
 * value objects and sane values.
 */
class ParseLpaData
{
    /**
     * @codeCoverageIgnore
     */
    public function __construct(
        private LpaFactory $lpaFactory,
        private InstAndPrefImagesFactory $imagesFactory,
        private LpaDataFormatter $lpaDataFormatter,
        private FeatureEnabled $featureEnabled,
    ) {
    }

    /**
     * Attempts to convert the data arrays received via the various endpoints into an ArrayObject containing
     * scalar and object values.
     *
     * Currently, fairly naive in its assumption that the data types are stored under explicit keys, which
     * may change.
     *
     * @param  array{
     *     lpa: array,
     *     actor?: array,
     *     iap?: array,
     *     ...} $data
     * @return ArrayObject
     * @throws Exception
     */
    public function __invoke(array $data): ArrayObject
    {
        foreach ($data as $dataItemName => $dataItem) {
            switch ($dataItemName) {
                case 'lpa':
                    //introduce feature flag here #3551
                    //the lpaData array converted to object using hydrator
                    if (($this->featureEnabled)('support_datastore_lpas')) {
                        // Set asLpaStoreLpa to toggle the format of the response (but ensure its set to false before running tests)
                        $mockedCombinedLpa = self::getMockedCombinedFormat(false);
                        $data['lpa']       = ($this->lpaDataFormatter)($mockedCombinedLpa);
                    } else {
                        $data['lpa'] = $this->lpaFactory->createLpaFromData($dataItem);
                    }
                    break;
                case 'actor':
                    $data['actor']['details'] = $this->lpaFactory->createCaseActorFromData($dataItem['details']);
                    break;
                case 'iap':
                    $data['iap'] = $this->imagesFactory->createFromData($dataItem);
                    break;
                default:
                    if (is_array($dataItem)) {
                        $data[$dataItemName] = ($this)($dataItem);
                    }
            }
        }

        return new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getMockedCombinedFormat(bool $asLpaStoreLpa): array
    {
        $lpaArray = [
            'id'                                        => 2,
            'uId'                                       => '700000000047',
            'receiptDate'                               => '2014-09-26',
            'registrationDate'                          => '2019-10-10',
            'rejectedDate'                              => null,
            'donor'                                     => [
                'id'          => 7,
                'uId'         => '700000000799',
                'linked'      => [['id' => 7, 'uId' => '700000000799']],
                'dob'         => '1948-11-01',
                'email'       => 'RachelSanderson@opgtest.com',
                'salutation'  => 'Mr',
                'firstname'   => 'Rachel',
                'middlenames' => 'Emma',
                'surname'     => 'Sanderson',
                'addresses'   => [
                    [
                        'id'           => 7,
                        'town'         => 'Townville',
                        'county'       => 'Countyville',
                        'postcode'     => 'DN37 5SH',
                        'country'      => '',
                        'type'         => 'Primary',
                        'addressLine1' => '81 Front Street',
                        'addressLine2' => 'LACEBY',
                        'addressLine3' => '',
                    ],
                ],
                'companyName' => null,
            ],
            'applicationType'                           => 'Classic',
            'caseSubtype'                               => 'pfa',
            'status'                                    => 'Registered',
            'lpaIsCleansed'                             => true,
            'caseAttorneySingular'                      => false,
            'caseAttorneyJointlyAndSeverally'           => true,
            'caseAttorneyJointly'                       => false,
            'caseAttorneyJointlyAndJointlyAndSeverally' => false,
            'onlineLpaId'                               => 'A33718377316',
            'cancellationDate'                          => null,
            'attorneys'                                 => [
                [
                    'id'           => 9,
                    'uId'          => '700000000815',
                    'dob'          => '1990-05-04',
                    'email'        => '',
                    'salutation'   => '',
                    'firstname'    => 'Jean',
                    'middlenames'  => '',
                    'surname'      => 'Sanderson',
                    'addresses'    => [
                        [
                            'id'           => 9,
                            'town'         => 'Pretendham',
                            'county'       => 'Countyville',
                            'postcode'     => 'DN37 5SH',
                            'country'      => '',
                            'type'         => 'Primary',
                            'addressLine1' => '9 High street',
                            'addressLine2' => 'Pretendville',
                            'addressLine3' => '',
                        ],
                    ],
                    'systemStatus' => true,
                    'companyName'  => '',
                ],
                [
                    'id'           => 12,
                    'uId'          => '7000-0000-0849',
                    'dob'          => '1975-10-05',
                    'email'        => 'XXXXX',
                    'salutation'   => 'Mrs',
                    'firstname'    => 'Ann',
                    'middlenames'  => '',
                    'surname'      => 'Summers',
                    'addresses'    => [
                        [
                            'id'           => 12,
                            'town'         => 'Hannerton',
                            'county'       => 'Countyville',
                            'postcode'     => 'HA1 4GH',
                            'country'      => '',
                            'type'         => 'Primary',
                            'addressLine1' => '47 Armington Way',
                            'addressLine2' => 'Hansville',
                            'addressLine3' => '',
                        ],
                    ],
                    'systemStatus' => true,
                    'companyName'  => '',
                ],
            ],
            'replacementAttorneys'                      => [],
            'trustCorporations'                         => [
                [
                    'addresses'    => [
                        [
                            'id'           => 3207,
                            'town'         => 'Town',
                            'county'       => 'County',
                            'postcode'     => 'ABC 123',
                            'country'      => 'GB',
                            'type'         => 'Primary',
                            'addressLine1' => 'Street 1',
                            'addressLine2' => 'Street 2',
                            'addressLine3' => 'Street 3',
                        ],
                    ],
                    'id'           => 3485,
                    'uId'          => '7000-0015-1998',
                    'dob'          => null,
                    'email'        => null,
                    'salutation'   => null,
                    'firstname'    => 'trust',
                    'middlenames'  => null,
                    'surname'      => 'test',
                    'otherNames'   => null,
                    'systemStatus' => true,
                    'name'         => 'Trust Us Corporation Ltd.',
                ],
            ],
            'certificateProviders'                      => [
                [
                    'id'          => 11,
                    'uId'         => '7000-0000-0831',
                    'dob'         => null,
                    'email'       => null,
                    'salutation'  => 'Miss',
                    'firstname'   => 'Danielle',
                    'middlenames' => null,
                    'surname'     => 'Hart ',
                    'addresses'   => [
                        [
                            'id'           => 11,
                            'town'         => 'Townville',
                            'county'       => '',
                            'postcode'     => 'SK14 0RH',
                            'country'      => '',
                            'type'         => 'Primary',
                            'addressLine1' => '50 Fordham Rd',
                            'addressLine2' => 'HADFIELD',
                            'addressLine3' => '',
                        ],
                    ],
                ],
            ],
            'whenTheLpaCanBeUsed'                       => 'when-has-capacity',
            'applicationHasRestrictions'                => false,
            'applicationHasGuidance'                    => false,
            'lpaDonorSignatureDate'                     => '2012-12-12',
            'lifeSustainingTreatment'                   => 'Option A',
            'howAttorneysMakeDecisions'                 => 'jointly',
        ];

        if ($asLpaStoreLpa) {
            $lpaArray['uId']                       = 'M-123412341234';
            $lpaArray['howAttorneysMakeDecisions'] = 'jointly-and-severally';
            $lpaArray['lpaType']                   = 'property-and-affairs';
            $lpaArray['lifeSustainingTreatment']   = 'option-a';
            $lpaArray['whenTheLpaCanBeUsed']       = 'when-has-capacity';

            $lpaArray['donor']['address']['line1']    = $lpaArray['donor']['addresses'][0]['addressLine1'];
            $lpaArray['donor']['address']['line2']    = $lpaArray['donor']['addresses'][0]['addressLine2'];
            $lpaArray['donor']['address']['line3']    = $lpaArray['donor']['addresses'][0]['addressLine3'];
            $lpaArray['donor']['address']['town']     = $lpaArray['donor']['addresses'][0]['town'];
            $lpaArray['donor']['address']['postcode'] = $lpaArray['donor']['addresses'][0]['postcode'];
            $lpaArray['donor']['address']['county']   = $lpaArray['donor']['addresses'][0]['county'];
            $lpaArray['donor']['address']['country']  = $lpaArray['donor']['addresses'][0]['country'];
            $lpaArray['donor']['dateOfBirth']         = $lpaArray['donor']['dob'];

            unset($lpaArray['donor']['addresses']);
            unset($lpaArray['donor']['dob']);

            $lpaArray['attorneys'][0]['address']['line1']    = $lpaArray['attorneys'][0]['addresses'][0]['addressLine1'];
            $lpaArray['attorneys'][0]['address']['line2']    = $lpaArray['attorneys'][0]['addresses'][0]['addressLine2'];
            $lpaArray['attorneys'][0]['address']['line3']    = $lpaArray['attorneys'][0]['addresses'][0]['addressLine3'];
            $lpaArray['attorneys'][0]['address']['town']     = $lpaArray['attorneys'][0]['addresses'][0]['town'];
            $lpaArray['attorneys'][0]['address']['postcode'] = $lpaArray['attorneys'][0]['addresses'][0]['postcode'];
            $lpaArray['attorneys'][0]['address']['county']   = $lpaArray['attorneys'][0]['addresses'][0]['county'];
            $lpaArray['attorneys'][0]['address']['country']  = $lpaArray['attorneys'][0]['addresses'][0]['country'];
            $lpaArray['attorneys'][0]['dateOfBirth']         = $lpaArray['attorneys'][0]['dob'];

            unset($lpaArray['attorneys'][0]['addresses']);
            unset($lpaArray['attorneys'][0]['dob']);

            $lpaArray['attorneys'][1]['address']['line1']    = $lpaArray['attorneys'][1]['addresses'][0]['addressLine1'];
            $lpaArray['attorneys'][1]['address']['line2']    = $lpaArray['attorneys'][1]['addresses'][0]['addressLine2'];
            $lpaArray['attorneys'][1]['address']['line3']    = $lpaArray['attorneys'][1]['addresses'][0]['addressLine3'];
            $lpaArray['attorneys'][1]['address']['town']     = $lpaArray['attorneys'][1]['addresses'][0]['town'];
            $lpaArray['attorneys'][1]['address']['postcode'] = $lpaArray['attorneys'][1]['addresses'][0]['postcode'];
            $lpaArray['attorneys'][1]['address']['county']   = $lpaArray['attorneys'][1]['addresses'][0]['county'];
            $lpaArray['attorneys'][1]['address']['country']  = $lpaArray['attorneys'][1]['addresses'][0]['country'];
            $lpaArray['attorneys'][1]['dateOfBirth']         = $lpaArray['attorneys'][1]['dob'];

            unset($lpaArray['attorneys'][1]['addresses']);
            unset($lpaArray['attorneys'][1]['dob']);

            $lpaArray['trustCorporations'][0]['address']['line1']    = $lpaArray['trustCorporations'][0]['addresses'][0]['addressLine1'];
            $lpaArray['trustCorporations'][0]['address']['line2']    = $lpaArray['trustCorporations'][0]['addresses'][0]['addressLine2'];
            $lpaArray['trustCorporations'][0]['address']['line3']    = $lpaArray['trustCorporations'][0]['addresses'][0]['addressLine3'];
            $lpaArray['trustCorporations'][0]['address']['town']     = $lpaArray['trustCorporations'][0]['addresses'][0]['town'];
            $lpaArray['trustCorporations'][0]['address']['postcode'] = $lpaArray['trustCorporations'][0]['addresses'][0]['postcode'];
            $lpaArray['trustCorporations'][0]['address']['county']   = $lpaArray['trustCorporations'][0]['addresses'][0]['county'];
            $lpaArray['trustCorporations'][0]['address']['country']  = $lpaArray['trustCorporations'][0]['addresses'][0]['country'];

            unset($lpaArray['trustCorporations'][0]['addresses']);
        }

        return $lpaArray;
    }
}
