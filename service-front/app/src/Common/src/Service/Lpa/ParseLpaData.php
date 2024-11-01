<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
use Common\Entity\CombinedLpa;
use Common\Entity\Person;
use Common\Enum\LifeSustainingTreatment;
use Common\Enum\LpaType;
use DateTimeImmutable;
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
                    if ($this->featureFlags['support_datastore_lpas'] ?? false) {
                        $data['lpa'] = ($this->lpaDataFormatter)($dataItem);
                    }
                    else {
                        if($data['user-lpa-actor-token'] == '1600be0d-727c-41aa-a9cb-45857a73ba4f') {
                            $data['lpa'] = $this->getCombinedMockData();
                        } else if($data['user-lpa-actor-token'] == 'f1315df5-b7c3-430a-baa0-9b96cc629648') {
                            $data['lpa'] = $this->getCombinedMockData();
                        }

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

    private function getCombinedMockData(): CombinedLpa
    {
        return new CombinedLpa(
            applicationHasGuidance: false,
            $applicationHasRestrictions = false,
            $applicationType            = 'Classic',
            $attorneyActDecisions       = null,
            $attorneys                  = [
                [
                    'addressLine1' => '9 high street',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'dob'          => null,
                    'email'        => '',
                    'firstname'    => 'A',
                    'firstnames'   => null,
                    'name'         => null,
                    'otherNames'   => null,
                    'postcode'     => 'DN37 5SH',
                    'surname'      => 'B',
                    'systemStatus' => '1',
                    'town'         => '',
                    'type'         => 'Primary',
                    'uId'          => '345678901',
                ],
                [
                    'addressLine1' => '',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'dob'          => null,
                    'email'        => 'XXXXX',
                    'firstname'    => 'B',
                    'firstnames'   => null,
                    'name'         => null,
                    'otherNames'   => null,
                    'postcode'     => '',
                    'surname'      => 'C',
                    'systemStatus' => '1',
                    'town'         => '',
                    'type'         => 'Primary',
                    'uId'          => '456789012',
                ],
                [
                    'addressLine1' => '',
                    'addressLine2' => '',
                    'addressLine3' => '',
                    'country'      => '',
                    'county'       => '',
                    'dob'          => null,
                    'email'        => 'XXXXX',
                    'firstname'    => 'C',
                    'firstnames'   => null,
                    'name'         => null,
                    'otherNames'   => null,
                    'postcode'     => '',
                    'surname'      => 'D',
                    'systemStatus' => '1',
                    'town'         => '',
                    'type'         => 'Primary',
                    'uId'          => '567890123',
                ],
            ],
            $caseSubtype                = LpaType::fromShortName('personal-welfare'),
            $channel                    = null,
            $dispatchDate               = null,
            $donor                      = new Person(
                $addressLine1           = '81 Front Street',
                $addressLine2           = 'LACEBY',
                $addressLine3           = '',
                $country                = '',
                $county                 = '',
                $dob                    = null,
                $email                  = 'RachelSanderson@opgtest.com',
                $firstname              = 'Rachel',
                $firstnames             = null,
                $name                   = null,
                $otherNames             = null,
                $postcode               = 'DN37 5SH',
                $surname                = 'Sanderson',
                $systemStatus           = null,
                $town                   = '',
                $type                   = 'Primary',
                $uId                    = '123456789',
            ),
            $hasSeveranceWarning        = null,
            $invalidDate                = null,
            $lifeSustainingTreatment    = LifeSustainingTreatment::fromShortName('Option A'),
            $lpaDonorSignatureDate      = new DateTimeImmutable('2012-12-12'),
            $lpaIsCleansed              = true,
            $onlineLpaId                = 'A33718377316',
            $receiptDate                = new DateTimeImmutable('2014-09-26'),
            $registrationDate           = new DateTimeImmutable('2019-10-10'),
            $rejectedDate               = null,
            $replacementAttorneys       = [],
            $status                     = 'Registered',
            $statusDate                 = null,
            $trustCorporations          = [
                [
                    'addressLine1' => 'Street 1',
                    'addressLine2' => 'Street 2',
                    'addressLine3' => 'Street 3',
                    'country'      => 'GB',
                    'county'       => 'County',
                    'dob'          => null,
                    'email'        => null,
                    'firstname'    => 'trust',
                    'firstnames'   => null,
                    'name'         => 'A',
                    'otherNames'   => null,
                    'postcode'     => 'ABC 123',
                    'surname'      => 'test',
                    'systemStatus' => '1',
                    'town'         => 'Town',
                    'type'         => 'Primary',
                    'uId'          => '678901234',
                ],
                [
                    'addressLine1' => 'Street 1',
                    'addressLine2' => 'Street 2',
                    'addressLine3' => 'Street 3',
                    'country'      => 'GB',
                    'county'       => 'County',
                    'dob'          => null,
                    'email'        => null,
                    'firstname'    => 'trust',
                    'firstnames'   => null,
                    'name'         => 'B',
                    'otherNames'   => null,
                    'postcode'     => 'ABC 123',
                    'surname'      => 'test',
                    'systemStatus' => '1',
                    'town'         => 'Town',
                    'type'         => 'Primary',
                    'uId'          => '789012345',
                ],
            ],
            $uId                        = '700000000047',
            $withdrawnDate              = null
        );
    }
}
