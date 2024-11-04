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
     * @param array{
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
                        $mockedCombinedLpa = $this->getMockedCombinedFormat();
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

    private function getMockedCombinedFormat(): array
    {
        return [
            'id' => 2,
            'uId' => '700000000047',
            'receiptDate' => '2014-09-26',
            'registrationDate' => '2019-10-10',
            'rejectedDate' => null,
            'donor' => [
                'id' => 7,
                'uId' => '700000000799',
                'linked' => [['id' => 7, 'uId' => '700000000799']],
                'dob' => '1948-11-01',
                'email' => 'RachelSanderson@opgtest.com',
                'salutation' => 'Mr',
                'firstname' => 'Rachel',
                'middlenames' => 'Emma',
                'surname' => 'Sanderson',
                'addresses' => [
                    [
                        'id' => 7,
                        'town' => '',
                        'county' => '',
                        'postcode' => 'DN37 5SH',
                        'country' => '',
                        'type' => 'Primary',
                        'addressLine1' => '81 Front Street',
                        'addressLine2' => 'LACEBY',
                        'addressLine3' => '',
                    ],
                ],
                'companyName' => null,
            ],
            'applicationType' => 'Classic',
            'caseSubtype' => 'hw',
            'status' => 'Registered',
            'lpaIsCleansed' => true,
            'caseAttorneySingular' => false,
            'caseAttorneyJointlyAndSeverally' => true,
            'caseAttorneyJointly' => false,
            'caseAttorneyJointlyAndJointlyAndSeverally' => false,
            'onlineLpaId' => 'A33718377316',
            'cancellationDate' => null,
            'attorneys' => [
                [
                    'id' => 9,
                    'uId' => '700000000815',
                    'dob' => '1990-05-04',
                    'email' => '',
                    'salutation' => '',
                    'firstname' => 'jean',
                    'middlenames' => '',
                    'surname' => 'sanderson',
                    'addresses' => [
                        [
                            'id' => 9,
                            'town' => '',
                            'county' => '',
                            'postcode' => 'DN37 5SH',
                            'country' => '',
                            'type' => 'Primary',
                            'addressLine1' => '9 high street',
                            'addressLine2' => '',
                            'addressLine3' => '',
                        ],
                    ],
                    'systemStatus' => true,
                    'companyName' => '',
                ],
                [
                    'id' => 12,
                    'uId' => '7000-0000-0849',
                    'dob' => '1975-10-05',
                    'email' => 'XXXXX',
                    'salutation' => 'Mrs',
                    'firstname' => 'Ann',
                    'middlenames' => '',
                    'surname' => 'Summers',
                    'addresses' => [
                        [
                            'id' => 12,
                            'town' => '',
                            'county' => '',
                            'postcode' => '',
                            'country' => '',
                            'type' => 'Primary',
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                        ],
                    ],
                    'systemStatus' => true,
                    'companyName' => '',
                ],
            ],
            'replacementAttorneys' => [],
            'trustCorporations' => [
                [
                    'addresses' => [
                        [
                            'id' => 3207,
                            'town' => 'Town',
                            'county' => 'County',
                            'postcode' => 'ABC 123',
                            'country' => 'GB',
                            'type' => 'Primary',
                            'addressLine1' => 'Street 1',
                            'addressLine2' => 'Street 2',
                            'addressLine3' => 'Street 3',
                        ],
                    ],
                    'id' => 3485,
                    'uId' => '7000-0015-1998',
                    'dob' => null,
                    'email' => null,
                    'salutation' => null,
                    'firstname' => 'trust',
                    'middlenames' => null,
                    'surname' => 'test',
                    'otherNames' => null,
                    'systemStatus' => true,
                    'companyName' => 'trust corporation',
                ],
            ],
            'certificateProviders' => [
                [
                    'id' => 11,
                    'uId' => '7000-0000-0831',
                    'dob' => null,
                    'email' => null,
                    'salutation' => 'Miss',
                    'firstname' => 'Danielle',
                    'middlenames' => null,
                    'surname' => 'Hart ',
                    'addresses' => [
                        [
                            'id' => 11,
                            'town' => '',
                            'county' => '',
                            'postcode' => 'SK14 0RH',
                            'country' => '',
                            'type' => 'Primary',
                            'addressLine1' => '50 Fordham Rd',
                            'addressLine2' => 'HADFIELD',
                            'addressLine3' => '',
                        ],
                    ],
                ],
            ],
            'attorneyActDecisions' => null,
            'applicationHasRestrictions' => false,
            'applicationHasGuidance' => false,
            'lpaDonorSignatureDate' => '2012-12-12',
            'lifeSustainingTreatment' => 'Option A',
        ];
    }
}
