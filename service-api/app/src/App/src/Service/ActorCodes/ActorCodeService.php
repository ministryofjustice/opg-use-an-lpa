<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\DataAccess\Repository;
use App\Service\Lpa\LpaService;

class ActorCodeService
{
    /**
     * @var Repository\ActorCodesInterface
     */
    private $actorCodesRepository;

    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * ActorCodeService constructor.
     * @param Repository\ActorCodesInterface $viewerCodesRepository
     * @param LpaService $lpaService
     */
    public function __construct(
        Repository\ActorCodesInterface $viewerCodesRepository,
        LpaService $lpaService
    )
    {
        $this->lpaService = $lpaService;
        $this->actorCodesRepository = $viewerCodesRepository;
    }

    /**
     * Returns null if details are not valid.
     * Or an array holding the usage_token if the LPA is added successfully.
     *
     * @param string $code
     * @param string $uid
     * @param string $dob
     * @return array|null
     */
    public function confirmDetails(string $code, string $uid, string $dob) : ?array {

        $details = $this->validateDetails($code, $uid, $dob);

        // If the details don't validate, stop here.
        if (is_null($details)) {
            return null;
        }

        //---

        /*
         * Transaction:
         *      1 - Create row in our table
         *      2 - Mark the code as used
         *      3 - Rollback 1 if 2 fails.
         */


        var_dump($details); die;
    }

    /**
     * @param string $code
     * @param string $uid
     * @param string $dob
     * @return array|null
     */
    public function validateDetails(string $code, string $uid, string $dob) : ?array
    {
        //-----------------------
        // Lookup the LPA and the Actor's LPA ID.

        $details = $this->actorCodesRepository->get($code);

        if (is_null($details)) {
            return null;
        }

        //-----------------------
        // Lookup the full LPA

        $lpa = $this->lpaService->getByUid($details['SiriusUid']);

        if (is_null($lpa)) {
            return null;
        }

        //----------------------
        // Find the actor in the LPA

        # TODO: Should this be a function somewhere?

        $actor = null;
        $actorType = null;

        // Determine if the actor is a primary attorney
        if (isset($lpa['attorneys']) && is_array($lpa['attorneys'])) {
            foreach ($lpa['attorneys'] as $attorney) {
                if ($attorney['id'] == $details['ActorLpaId']) {
                    $actor = $attorney;
                    $actorType = 'primary-attorney';
                    break;
                }
            }
        }

        // If no an attorney, check if they're the donor.
        if (is_null($actor) &&
            isset($lpa['donor']) &&
            is_array($lpa['donor']) &&
            $lpa['donor']['id'] == $details['ActorLpaId'])
        {
            $actor = $lpa['donor'];
            $actorType = 'donor';
        }

        if (is_null($actor)) {
            return null;
        }

        //----------------------
        // Validate the details match

        if ($code != $details['ActorCode'] || $uid != $lpa['uId'] || $dob != $actor['dob']) {
            return null;
        }


        //---

        return [
            'lpa' => $lpa,
            'actor' => [
                'tpye' => $actorType,
                'details' => $actor
            ]
        ];

    }

}
