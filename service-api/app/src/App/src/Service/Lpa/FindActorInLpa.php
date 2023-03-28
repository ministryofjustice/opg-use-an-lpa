<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;

class FindActorInLpa
{
    public const MATCH                        = 0;
    public const NO_MATCH__MULTIPLE_ADDRESSES = 1;
    public const NO_MATCH__DOB                = 2;
    public const NO_MATCH__FIRSTNAMES         = 4;
    public const NO_MATCH__SURNAME            = 8;
    public const NO_MATCH__POSTCODE           = 16;

    public function __construct(private GetAttorneyStatus $getAttorneyStatus, private LoggerInterface $logger)
    {
    }

    public function __invoke(array $lpa, array $matchData): ?array
    {
        $actor = null;
        $role  = null;

        if (isset($lpa['attorneys']) && is_array($lpa['attorneys'])) {
            [$actor, $role] = $this->findAttorneyDetails($lpa['attorneys'], $matchData, $lpa['uId']);
        }

        // If not an attorney, check if they're the donor.
        if ($actor === null && isset($lpa['donor']) && is_array($lpa['donor'])) {
            [$actor, $role] = $this->checkDonorDetails($lpa['donor'], $matchData);
        }

        if ($actor === null) {
            return null;
        }

        return [
            'actor'  => $actor,
            'role'   => $role,
            'lpa-id' => $lpa['uId'],
        ];
    }

    private function checkForAttorneyMatch(array $attorney, array $matchData, string $lpaId): array
    {
        if (($this->getAttorneyStatus)($attorney) !== GetAttorneyStatus::ACTIVE_ATTORNEY) {
            $this->logger->info(
                'Actor {id} status is not active for LPA {uId}',
                [
                    'id'  => $attorney['uId'],
                    'uId' => $lpaId,
                ]
            );

            return [null, null];
        }

        $actorMatchResponse = $this->checkForActorMatch($attorney, $matchData);

        if ($actorMatchResponse === self::MATCH) {
            return [$attorney, 'attorney'];
        }

        return [null, null];
    }

    private function checkDonorDetails(array $donor, array $matchData): array
    {
        $donorMatchResponse = $this->checkForActorMatch($donor, $matchData);

        if ($donorMatchResponse === self::MATCH) {
            return [$donor, 'donor'];
        }

        return [null, null];
    }

    private function findAttorneyDetails(array $attorneys, array $matchData, string $lpaId): array
    {
        foreach ($attorneys as $attorney) {
            [$actor, $role] = $this->checkForAttorneyMatch($attorney, $matchData, $lpaId);

            if ($actor !== null) {
                return [$actor, $role];
            }
        }

        return [null, null];
    }

    /**
     * Compares LPA data retrieved from Sirius to the data provided by
     * the user to check if it matches
     *
     * @param array $actor     The actor details being compared against
     * @param array $matchData The user provided data we're searching for a match against
     * @return int A bitfield containing the failure to match reasons, or 0 if it matched.
     */
    private function checkForActorMatch(array $actor, array $matchData): int
    {
        // Check if the actor has more than one address
        if (count($actor['addresses']) > 1) {
            $this->logger->notice(
                'Data match failed for actor {id} as more than 1 address found',
                [
                    'id' => $actor['uId'],
                ]
            );
            return self::NO_MATCH__MULTIPLE_ADDRESSES;
        }

        $matchData = $this->normaliseComparisonData($matchData);
        $actorData = $this->normaliseComparisonData(
            [
                'first_names' => $actor['firstname'],
                'last_name'   => $actor['surname'],
                'postcode'    => $actor['addresses'][0]['postcode'],
            ]
        );

        $this->logger->debug(
            'Doing actor data comparison against actor with id {actor_id}',
            [
                'actor_id'   => $actor['uId'],
                'to_match'   => $matchData,
                'actor_data' => array_merge($actorData, ['dob' => $actor['dob']]),
            ]
        );

        $match = self::MATCH;

        $match = $actor['dob'] !== $matchData['dob'] ? $match | self::NO_MATCH__DOB : $match;
        $match = $actorData['first_names'] !== $matchData['first_names']
            ? $match | self::NO_MATCH__FIRSTNAMES
            : $match;
        $match = $actorData['last_name'] !== $matchData['last_name']
            ? $match | self::NO_MATCH__SURNAME
            : $match;
        $match = $actorData['postcode'] !== $matchData['postcode']
            ? $match | self::NO_MATCH__POSTCODE
            : $match;

        if ($match === self::MATCH) {
            $this->logger->info(
                'User entered data matches for LPA {uId}',
                [
                    'uId' => $matchData['reference_number'],
                ]
            );
        } else {
            $this->logger->info(
                'User entered data failed to match for LPA {uId} and actor {actor_id}. '
                    . 'Fields in error: {fields_in_error}',
                [
                    'uId'             => $matchData['reference_number'],
                    'actor_id'        => $actor['uId'],
                    'fields_in_error' => $match,
                ]
            );
        }

        return $match;
    }

    /**
     * Formats data attributes for comparison in the older lpa journey
     *
     * @param array $data
     * @return ?array
     */
    private function normaliseComparisonData(array $data): ?array
    {
        $data['first_names'] = strtolower(explode(' ', $data['first_names'])[0]);
        $data['last_name']   = strtolower($data['last_name']);
        $data['postcode']    = strtolower(str_replace(' ', '', $data['postcode']));

        return $data;
    }
}
