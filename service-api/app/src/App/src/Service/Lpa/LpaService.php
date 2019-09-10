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
        //  For now load the data from the local json file
        $data = file_get_contents(__DIR__ . '/lpas-gateway.json');
        $lpaDatasets = json_decode($data, true);

        foreach ($lpaDatasets as $lpaDataset) {
            //  Filter dashes out of the Sirius Uid before comparison
            $siriusUid = str_replace('-', '', $lpaDataset['uId']);

            if ($siriusUid == $lpaId) {
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

        return $this->getById($viewerCodeData['SiriusUid']);
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

            if ($uid != $actorLpaCodeData['SiriusUid']) {
                throw new NotFoundException();
            }

            $lpaData = $this->getById($uid);

            //  Use the actor sirius Uid and dob provided to confirm that this LPA data should be returned
            $actorSiriusUid = $actorLpaCodeData['ActorSiriusUid'];

            //  Try to find actor data based on the Uid
            $actorData = [];

            if (isset($lpaData['donor']['uId']) && str_replace('-', '', $lpaData['donor']['uId']) == $actorSiriusUid) {
                $actorData = $lpaData['donor'];
            } elseif (isset($lpaData['attorneys']) && is_array($lpaData['attorneys'])) {
                foreach ($lpaData['attorneys'] as $attorney) {
                    if (isset($attorney['uId']) && str_replace('-', '', $attorney['uId']) == $actorSiriusUid) {
                        $actorData = $attorney;
                    }
                }
            }

            //  Compare the date of birth for the actor
            if (!empty($actorData)
                && isset($actorData['dob'])
                && $actorData['dob'] == $dob) {

                return $lpaData;
            }

            throw new NotFoundException();
        } catch (NotFoundException $nfe) {
            //  Repackage the NotFoundException to remove the code based exception
            throw new NotFoundException('No LPA found');
        }
    }
}
