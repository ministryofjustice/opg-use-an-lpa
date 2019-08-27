<?php

namespace App\Service\Lpa;

use App\DataAccess\Repository;
use App\Exception\NotFoundException;
use App\Exception\GoneException;
use DateTime;

/**
 * Class LpaService
 * @package App\Service\Lpa
 */
class LpaService
{
    /**
     * @var Repository\ActorLpaCodesInterface
     */
    private $actorLpaCodesRepository;

    /**
     * @var Repository\ViewerCodesInterface
     */
    private $viewerCodesRepository;

    /**
     * @var Repository\ViewerCodeActivityInterface
     */
    private $viewerCodeActivityRepository;

    /**
     * LpaService constructor.
     * @param Repository\ViewerCodesInterface $viewerCodesRepository
     * @param Repository\ViewerCodeActivityInterface $viewerCodeActivityRepository
     * @param Repository\ActorLpaCodesInterface $actorLpaCodesRepository
     */
    public function __construct(
        Repository\ViewerCodesInterface $viewerCodesRepository,
        Repository\ViewerCodeActivityInterface $viewerCodeActivityRepository,
        Repository\ActorLpaCodesInterface $actorLpaCodesRepository
    )
    {
        $this->viewerCodesRepository = $viewerCodesRepository;
        $this->viewerCodeActivityRepository = $viewerCodeActivityRepository;
        $this->actorLpaCodesRepository = $actorLpaCodesRepository;
    }

    /**
     * Get an LPA using the ID value
     *
     * @param string $lpaId
     * @return array
     * @throws NotFoundException
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
     * @throws GoneException
     */
    public function getByCode(string $shareCode) : array
    {
        $viewerCodeData = $this->viewerCodesRepository->get($shareCode);

        if ($viewerCodeData['Expires'] < new DateTime()) {
            throw new GoneException('Share code expired');
        }

        //  Record the lookup in the activity table
        $this->viewerCodeActivityRepository->recordSuccessfulLookupActivity($viewerCodeData['ViewerCode']);

        return $this->getById($viewerCodeData['SiriusId']);
    }

    /**
     * Search for an LPA actor code
     *
     * @param string $code
     * @param string $uid
     * @param string $dob
     * @return array
     */
    public function search(string $code, string $uid, string $dob)
    {
        try {
            $actorLpaCodeData = $this->actorLpaCodesRepository->get($code);

            if ($uid != $actorLpaCodeData['SiriusId']) {
                throw new NotFoundException();
            }

            $lpaData = $this->getById($uid);

            if (empty($lpaData)) {
                throw new NotFoundException();
            }

            //  Compare the date of birth given for the donor or attorneys
            //TODO - Use the specific actor type here? (e.g. Attorney 1, Donor, etc)
            if (isset($lpaData['donor']['dob'])) {
                $donorDob = new DateTime($lpaData['donor']['dob']);

                if ($donorDob->format('Y-m-d') == $dob) {
                    return $lpaData;
                }
            }

            if (isset($lpaData['attorneys']) && is_array($lpaData['attorneys'])) {
                foreach ($lpaData['attorneys'] as $attorney) {
                    if (isset($attorney['dob'])) {
                        $attorneyDob = new DateTime($attorney['dob']);

                        if ($attorneyDob->format('Y-m-d') == $dob) {
                            return $lpaData;
                        }
                    }
                }
            }

            throw new NotFoundException();
        } catch (NotFoundException $nfe) {
            //  Repackage the NotFoundException to remove the code based exception
            throw new NotFoundException('No LPA found');
        }
    }

    /**
     * TODO - Mock LPA data....to be removed when Sirius connectivity is established
     *
     * @var array
     */
    private $lpaDatasets = [
        [
            'id' => '123456789012',
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
            'id' => '987654321098',
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
