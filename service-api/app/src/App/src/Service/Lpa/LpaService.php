<?php

namespace App\Service\Lpa;

use App\Exception\NotFoundException;
use App\Exception\PageExpiredException;
use Aws\DynamoDb\DynamoDbClient;
use DateTime;

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
        //  TODO - Remove the use of mock data when connected to Sirius gateway
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
     * @throws \Exception
     */
    public function getByCode(string $shareCode) : array
    {
        //  Query Dynamo DB for the code
        $result = $this->dynamoDbClient->getItem([
            'TableName' => 'ViewerCodes',
            'Key' => [
                'ViewerCode' => [
                    'S' => $shareCode,
                ],
            ],
        ]);

        $expires = $result->search('Item.Expires.S');

        if (!is_null($expires)) {
            //  Check that the share code hasn't expired
            $expiresDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $expires);

            if ($expiresDateTime < new DateTime()) {
                throw new PageExpiredException('Share code expired');
            }

            $siriusId = $result->search('Item.SiriusId.S');

            return $this->getById($siriusId);
        }

        throw new NotFoundException('Code not found');
    }


    /**
     * TODO - Mock LPA data....to be removed when Sirius connectivity is established
     *
     * @var array
     */
    private $lpaDatasets = [
        [
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
        [
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
