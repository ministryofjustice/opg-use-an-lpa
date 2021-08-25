<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;

class FindActorInLpa
{
    private GetAttorneyStatus $getAttorneyStatus;
    private LoggerInterface $logger;

    public function __construct(GetAttorneyStatus $getAttorneyStatus, LoggerInterface $logger)
    {
        $this->getAttorneyStatus = $getAttorneyStatus;
        $this->logger = $logger;
    }

    public function __invoke(array $lpa, array $matchData): ?array
    {
        $actor = null;
        $lpaId = $lpa['uId'];

        if (isset($lpa['attorneys']) && is_array($lpa['attorneys'])) {
            foreach ($lpa['attorneys'] as $attorney) {
                if (($this->getAttorneyStatus)($attorney) === GetAttorneyStatus::ACTIVE_ATTORNEY) {
                    $actorMatchResponse = $this->checkForActorMatch($attorney, $matchData);
                    // if not null, an actor match has been found
                    if ($actorMatchResponse !== null) {
                        $actor = $actorMatchResponse;
                        $role = 'attorney';
                        break;
                    }
                } else {
                    $this->logger->info(
                        'Actor {id} status is not active for LPA {uId}',
                        [
                            'id' => $attorney['uId'],
                            'uId' => $lpaId,
                        ]
                    );
                }
            }
        }

        // If not an attorney, check if they're the donor.
        if ($actor === null && isset($lpa['donor']) && is_array($lpa['donor'])) {
            $donorMatchResponse = $this->checkForActorMatch($lpa['donor'], $matchData);

            if ($donorMatchResponse !== null) {
                $actor = $donorMatchResponse;
                $role = 'donor';
            }
        }

        if (is_null($actor)) {
            return null;
        }

        return [
            'actor' => $actor,
            'role'  => $role,
            'lpa-id' => $lpaId,
        ];
    }

    /**
     * Compares LPA data retrieved from Sirius to the data provided by
     * the user to check if it matches
     *
     * @param array $actor     The actor details being compared against
     * @param array $matchData The user provided data we're searching for a match against
     *
     * @return ?array A data structure containing the matched actor id and lpa id
     */
    private function checkForActorMatch(array $actor, array $matchData): ?array
    {
        // Check if the actor has more than one address
        if (count($actor['addresses']) > 1) {
            $this->logger->notice(
                'Data match failed for actor {id} as more than 1 address found',
                [
                    'id' => $actor['uId'],
                ]
            );
            return null;
        }

        $matchData = $this->normaliseComparisonData($matchData);
        $actorData = $this->normaliseComparisonData(
            [
                'first_names'   => $actor['firstname'],
                'last_name'     => $actor['surname'],
                'postcode'      => $actor['addresses'][0]['postcode'],
            ]
        );

        $this->logger->debug(
            'Doing actor data comparison against actor with id {actor_id}',
            [
                'actor_id'      => $actor['uId'],
                'to_match'      => $matchData,
                'actor_data'    => array_merge($actorData, ['dob' => $actor['dob']]),
            ]
        );

        if (
            $actor['dob'] === $matchData['dob'] &&
            $actorData['first_names'] === $matchData['first_names'] &&
            $actorData['last_name'] === $matchData['last_name'] &&
            $actorData['postcode'] === $matchData['postcode']
        ) {
            $this->logger->info(
                'User entered data matches for LPA {uId}',
                [
                    'uId' => $matchData['reference_number'],
                ]
            );
            return $actor;
        }

        return null;
    }

    /**
     * Formats data attributes for comparison in the older lpa journey
     *
     * @param array $data
     *
     * @return ?array
     */
    private function normaliseComparisonData(array $data): ?array
    {
        $data['first_names'] = strtolower(explode(' ', $data['first_names'])[0]);
        $data['last_name'] = strtolower($data['last_name']);
        $data['postcode'] = strtolower(str_replace(' ', '', $data['postcode']));

        return $data;
    }
}
