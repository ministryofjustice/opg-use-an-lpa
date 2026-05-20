<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Entity\LpaStore\LpaStoreAttorney;
use App\Exception\ActorDateOfBirthNotSetException;
use App\Service\Equals;
use App\Service\Lpa\FindActorInLpa\ActorMatch;
use App\Service\Lpa\FindActorInLpa\ActorMatchingInterface;
use App\Service\Lpa\FindActorInLpa\FindActorInLpaInterface;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use Psr\Log\LoggerInterface;
use App\Service\Lpa\GetAttorneyStatus\AttorneyStatus;

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

    public function __invoke(FindActorInLpaInterface $lpa, array $matchData): ?ActorMatch
    {
        $actor = null;
        $role  = null;

        [
            $actor,
            $role,
        ] = $this->findAttorneyDetails($lpa->getAttorneys(), $matchData, $lpa->getUid());

        // If not an attorney, check if they're the donor.
        if ($actor === null) {
            [
                $actor,
                $role,
            ] = $this->checkDonorDetails($lpa->getDonor(), $matchData);
        }

        if ($actor === null) {
            return null;
        }

        return new ActorMatch($actor, $role, $lpa->getUid());
    }

    private function checkForAttorneyMatch(
        GetAttorneyStatusInterface&ActorMatchingInterface $attorney,
        array $matchData,
        string $lpaId,
    ): array {
        if (($this->getAttorneyStatus)($attorney) !== AttorneyStatus::ACTIVE_ATTORNEY) {
            $this->logger->info(
                'Actor {id} status is not active for LPA {uId}',
                [
                    'id'  => $attorney->getUid(),
                    'uId' => $lpaId,
                ]
            );

            return [
                null,
                null,
            ];
        }

        $actorMatchResponse = $this->checkForActorMatch($attorney, $matchData);

        if ($actorMatchResponse === self::MATCH) {
            return [
                $attorney,
                'attorney',
            ];
        }

        return [
            null,
            null,
        ];
    }

    private function checkDonorDetails(
        GetAttorneyStatusInterface&ActorMatchingInterface $donor,
        array $matchData,
    ): array {
        $donorMatchResponse = $this->checkForActorMatch($donor, $matchData);

        if ($donorMatchResponse === self::MATCH) {
            return [
                $donor,
                'donor',
            ];
        }

        return [
            null,
            null,
        ];
    }

    private function findAttorneyDetails(array $attorneys, array $matchData, string $lpaId): array
    {
        foreach ($attorneys as $attorney) {
            [
                $actor,
                $role,
            ] = $this->checkForAttorneyMatch($attorney, $matchData, $lpaId);

            if ($actor !== null) {
                return [
                    $actor,
                    $role,
                ];
            }
        }

        return [
            null,
            null,
        ];
    }

    /**
     * Compares LPA data retrieved from Sirius to the data provided by the user to check if it matches
     *
     * @param ActorMatchingInterface $actor     The actor details being compared against
     * @param array                  $matchData The user provided data we're searching for a match against
     * @return int A bitfield containing the failure to match reasons, or 0 if it matched.
     */
    private function checkForActorMatch(ActorMatchingInterface $actor, array $matchData): int
    {
        // Check if the actor has more than one address (only applies to old SiriusPerson class not new Person)
        if ($actor instanceof SiriusPerson) {
            if (count($actor['addresses']) > 1) {
                $this->logger->notice(
                    'Data match failed for actor {id} as more than 1 address found',
                    [
                        'id' => $actor->getUid(),
                    ]
                );
                return self::NO_MATCH__MULTIPLE_ADDRESSES;
            }
        }

        $this->logger->debug(
            'Doing actor data comparison against actor with id {actor_id}',
            [
                'actor_id' => $actor->getUid(),
                'to_match' => $matchData,
            ]
        );

        $match = self::MATCH;
        try {
            $match = $actor->getDob()->format('Y-m-d') !== $matchData['dob']
                ? $match | self::NO_MATCH__DOB
                : $match;
        } catch (ActorDateOfBirthNotSetException $exception) {
            $this->logger->warning(
                'Actor DOB is null',
                [
                    'actor_id' => $actor->getUid(),
                    'error'    => $exception->getMessage(),
                ]
            );

            return self::NO_MATCH__DOB;
        }

        $match = Equals::firstNames($actor->getFirstname(), $matchData['first_names'])
            ? $match
            : $match | self::NO_MATCH__FIRSTNAMES;
        $match = Equals::lastName($actor->getSurname(), $matchData['last_name'])
            ? $match
            : $match | self::NO_MATCH__SURNAME;
        $match = Equals::postcode($actor->getPostcode(), $matchData['postcode'])
            ? $match
            : $match | self::NO_MATCH__POSTCODE;

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
                    'actor_id'        => $actor->getUid(),
                    'fields_in_error' => $match,
                ]
            );
        }

        return $match;
    }
}
