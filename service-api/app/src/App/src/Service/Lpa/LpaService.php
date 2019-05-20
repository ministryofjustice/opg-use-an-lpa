<?php

namespace App\Service\Lpa;

use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;

/**
 * Class LpaService
 * @package App\Service\Lpa
 */
class LpaService
{
    /**
     * @var DynamoDbClient
     */
    private $dynamoDbClient;

    /**
     * LpaService constructor.
     * @param DynamoDbClient $dynamoDbClient
     */
    public function __construct(DynamoDbClient $dynamoDbClient)
    {
        $this->dynamoDbClient = $dynamoDbClient;
    }

    /**
     * Get an LPA using the ID value
     *
     * @param string $lpaId
     * @return array
     */
    public function getById(string $lpaId) : array
    {
        //  TODO - Implement the Dynamo query build here - for now just look for the hardcoded data

        foreach ($this->lpaDatasets as $lpaDataset) {
            if (isset($lpaDataset['id']) && $lpaDataset['id'] == $lpaId) {
                return $lpaDataset;
            }
        }

        throw new NotFoundException('LPA not found');
    }

    /**
     * Get an LPA using the share code
     *
     * @param string $shareCode
     * @return array
     */
    public function getByCode(string $shareCode) : array
    {
        //  TODO - Implement the Dynamo query build here - for now just look for the hardcoded data

        foreach ($this->lpaDatasets as $lpaShareCode => $lpaDataset) {
            if ($lpaShareCode == $shareCode) {
                return $lpaDataset;
            }
        }

        throw new NotFoundException('LPA not found');
    }


    /**
     * TODO - Mock LPA data....to be removed when Sirius connectivity is established
     *
     * @var array
     */
    private $lpaDatasets = [
        '123456789012' => [
            'id' => '12345678901',
            'caseNumber' => '787640393837',
            'type' => 'property-and-financial',
            'donor' => [
                'name' => [
                    'title' => 'Mr',
                    'first' => 'Jordan',
                    'last' => 'Johnson',
                ],
                'dob' => '1980-01-01T00:00:00+00:00',
                'address' => [
                    'address1' => '1 High Street',
                    'address2' => 'Hampton',
                    'address3' => 'Wessex',
                    'postcode' => 'LH1 7QQ',
                ],
            ],
            'attorneys' => [
                [
                    'name' => [
                        'title' => 'Mr',
                        'first' => 'Peter',
                        'last' => 'Smith',
                    ],
                    'dob' => '1984-02-14T00:00:00+00:00',
                    'address' => [
                        'address1' => '1 High Street',
                        'address2' => 'Hampton',
                        'address3' => 'Wessex',
                        'postcode' => 'LH1 7QQ',
                    ],
                ],
                [
                    'name' => [
                        'title' => 'Miss',
                        'first' => 'Celia',
                        'last' => 'Smith',
                    ],
                    'dob' => '1988-11-12T00:00:00+00:00',
                    'address' => [
                        'address1' => '1 Avenue Road',
                        'address2' => 'Great Hampton',
                        'address3' => 'Wessex',
                        'postcode' => 'LH4 8PU',
                    ],
                ],
            ],
            'decisions' => [
                'how' => 'jointly',
                'when' => 'no-capacity',
            ],
            'preferences' => false,
            'instructions' => false,
            'dateDonorSigned' => '2017-02-25T00:00:00+00:00',
            'dateRegistration' => '2017-04-15T00:00:00+00:00',
            'dateLastConfirmedStatus' => '2019-04-22T00:00:00+00:00',
        ],
        '987654321098' => [
            'id' => '98765432109',
            'caseNumber' => '787640393837',
            'type' => 'property-and-financial',
            'donor' => [
                'name' => [
                    'title' => 'Mr',
                    'first' => 'Jordan',
                    'last' => 'Johnson',
                ],
                'dob' => '1980-01-01T00:00:00+00:00',
                'address' => [
                    'address1' => '1 High Street',
                    'address2' => 'Hampton',
                    'address3' => 'Wessex',
                    'postcode' => 'LH1 7QQ',
                ],
            ],
            'attorneys' => [
                [
                    'name' => [
                        'title' => 'Mr',
                        'first' => 'Peter',
                        'last' => 'Smith',
                    ],
                    'dob' => '1984-02-14T00:00:00+00:00',
                    'address' => [
                        'address1' => '1 High Street',
                        'address2' => 'Hampton',
                        'address3' => 'Wessex',
                        'postcode' => 'LH1 7QQ',
                    ],
                ],
                [
                    'name' => [
                        'title' => 'Miss',
                        'first' => 'Celia',
                        'last' => 'Smith',
                    ],
                    'dob' => '1988-11-12T00:00:00+00:00',
                    'address' => [
                        'address1' => '1 Avenue Road',
                        'address2' => 'Great Hampton',
                        'address3' => 'Wessex',
                        'postcode' => 'LH4 8PU',
                    ],
                ],
            ],
            'decisions' => [
                'how' => 'jointly',
                'when' => 'no-capacity',
            ],
            'preferences' => false,
            'instructions' => false,
            'dateDonorSigned' => '2017-02-25T00:00:00+00:00',
            'dateRegistration' => '2017-04-15T00:00:00+00:00',
            'dateCancelled' => '2018-04-25T00:00:00+00:00',
        ],
    ];
}
