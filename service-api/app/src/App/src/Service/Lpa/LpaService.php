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
     * @var Repository\ActorCodesInterface
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
     * @var Repository\LpasInterface
     */
    private $lpaRepository;

    /**
     * LpaService constructor.
     * @param Repository\ViewerCodesInterface $viewerCodesRepository
     * @param Repository\ViewerCodeActivityInterface $viewerCodeActivityRepository
     * @param Repository\ActorCodesInterface $actorLpaCodesRepository
     */
    public function __construct(
        Repository\ViewerCodesInterface $viewerCodesRepository,
        Repository\ViewerCodeActivityInterface $viewerCodeActivityRepository,
        Repository\ActorCodesInterface $actorLpaCodesRepository,
        Repository\LpasInterface $lpaRepository
    )
    {
        $this->viewerCodesRepository = $viewerCodesRepository;
        $this->viewerCodeActivityRepository = $viewerCodeActivityRepository;
        $this->actorLpaCodesRepository = $actorLpaCodesRepository;

        $this->lpaRepository = $lpaRepository;
    }

    /**
     * Get an LPA using the ID value
     *
     * @param string $uid
     * @return ?array
     */
    public function getByUid(string $uid) : ?array
    {
        return $this->lpaRepository->get($uid);
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
