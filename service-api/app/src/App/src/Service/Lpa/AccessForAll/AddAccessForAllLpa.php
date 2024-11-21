<?php

declare(strict_types=1);

namespace App\Service\Lpa\AccessForAll;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\AddLpa\LpaAlreadyAdded;
use App\Service\Lpa\FindActorInLpa;
use App\Service\Lpa\FindActorInLpa\ActorMatch;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\Lpa\RestrictSendingLpaForCleansing;
use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\ValidateAccessForAllLpaRequirements;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

class AddAccessForAllLpa
{
    /**
     * @codeCoverageIgnore
     * @param FindActorInLpa                      $findActorInLpa
     * @param LpaManagerInterface                 $lpaManager
     * @param LpaAlreadyAdded                     $lpaAlreadyAdded
     * @param AccessForAllLpaService              $accessForAllLpaService
     * @param ValidateAccessForAllLpaRequirements $validateAccessForAllLpaRequirements
     * @param RestrictSendingLpaForCleansing      $restrictSendingLpaForCleansing
     * @param LoggerInterface                     $logger
     */
    public function __construct(
        private FindActorInLpa $findActorInLpa,
        private LpaManagerInterface $lpaManager,
        private LpaAlreadyAdded $lpaAlreadyAdded,
        private AccessForAllLpaService $accessForAllLpaService,
        private ValidateAccessForAllLpaRequirements $validateAccessForAllLpaRequirements,
        private RestrictSendingLpaForCleansing $restrictSendingLpaForCleansing,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string                 $userId
     * @param int                    $referenceNumber
     * @param AccessForAllValidation $validationData
     * @param array                  $lpaAddedData
     * @return void
     * @throws BadRequestException
     */
    private function existentCodeNotActivated(
        string $userId,
        int $referenceNumber,
        AccessForAllValidation $validationData,
        array $lpaAddedData,
    ): void {
        $this->logger->notice(
            'User {id} attempted to request a key for the LPA {uId} which they have already requested',
            [
                'id'  => $userId,
                'uId' => $referenceNumber,
            ],
        );

        $activationKeyDueDate = $lpaAddedData['activationKeyDueDate'] ?? null;

        // if activation key due date is null, check activation code exist in sirius
        if ($activationKeyDueDate === null) {
            $hasActivationCode = $this->accessForAllLpaService->hasActivationCode(
                $validationData->lpa->getUid(),
                $validationData->actorMatch->actor->getUid(),
            );

            if ($hasActivationCode instanceof DateTime) {
                $activationKeyDueDate = DateTimeImmutable::createFromMutable($hasActivationCode);
                $activationKeyDueDate = $activationKeyDueDate
                    ->add(new DateInterval('P10D'))
                    ->format('Y-m-d');
            }
        }

        throw new BadRequestException(
            'Activation key already requested for LPA',
            [
                'donor'                => $lpaAddedData['donor'],
                'caseSubtype'          => $lpaAddedData['caseSubtype'],
                'activationKeyDueDate' => $activationKeyDueDate,
            ],
        );
    }

    /**
     * @param string                 $userId
     * @param int                    $referenceNumber
     * @param AccessForAllValidation $validationData
     * @param array{
     *     notActivated: bool
     * }|null                        $lpaAddedData
     * @return void
     * @throws BadRequestException
     */
    private function processActivationCode(
        string $userId,
        int $referenceNumber,
        AccessForAllValidation $validationData,
        ?array $lpaAddedData,
    ): void {
        if (isset($lpaAddedData['notActivated'])) {
            $this->existentCodeNotActivated($userId, $referenceNumber, $validationData, $lpaAddedData);
        }

        $hasActivationCode = $this->accessForAllLpaService->hasActivationCode(
            $validationData->actorMatch->lpaUId,
            $validationData->actorMatch->actor->getUid()
        );

        if ($hasActivationCode instanceof DateTime) {
            $activationKeyDueDate = DateTimeImmutable::createFromMutable($hasActivationCode);
            $activationKeyDueDate = $activationKeyDueDate
                ->add(new DateInterval('P10D'))
                ->format('Y-m-d');

            throw new BadRequestException(
                'LPA has an activation key already',
                [
                    'donor'                => $validationData->lpa->getDonor(),
                    'caseSubtype'          => $validationData->getCaseSubtype(),
                    'activationKeyDueDate' => $activationKeyDueDate,
                ]
            );
        }
    }

    /**
     * @param int $referenceNumber
     * @return SiriusLpa|null
     * @throws NotFoundException|Exception
     */
    private function fetchLPAData(int $referenceNumber): ?SiriusLpa
    {
        $lpa = $this->lpaManager->getByUid((string) $referenceNumber);
        if ($lpa === null) {
            $this->logger->info(
                'The LPA {uId} entered by user is not found in Sirius',
                [
                    'uId' => $referenceNumber,
                ]
            );
            throw new NotFoundException('LPA not found');
        }

        /** @var ?SiriusLpa */
        return $lpa->getData();
    }

    /**
     * Given a users ID, and an array containing an LPA reference number alongside some data that may, or may not
     * match an active actor within that LPA return a data array containing details about that LPA - otherwise
     * throw an exception that details why the match was not able to be made.
     *
     * @param string $userId The users database ID
     * @param array{
     *     reference_number: int,
     *     dob: string,
     *     first_names: string,
     *     last_name: string,
     *     post_code: string,
     *     force_activation_key: bool,
     * }             $matchData The user supplied information to attempt to match an LPA to
     * @return AccessForAllValidation
     * @throws BadRequestException|NotFoundException|Exception
     */
    public function validateRequest(string $userId, array $matchData): AccessForAllValidation
    {
        // Check if it's been added to the users account already
        $lpaAddedData = ($this->lpaAlreadyAdded)($userId, (string) $matchData['reference_number']);

        if ($lpaAddedData !== null && !array_key_exists('notActivated', $lpaAddedData)) {
            $this->logger->notice(
                'User {id} attempted to request a key for the LPA {uId} which already exists in their account',
                [
                    'id'  => $userId,
                    'uId' => (string) $matchData['reference_number'],
                ]
            );
            throw new BadRequestException('LPA already added', $lpaAddedData);
        }

        $lpa = $this->fetchLPAData($matchData['reference_number']);

        // Ensure LPA meets our registration requirements
        ($this->validateAccessForAllLpaRequirements)($lpa->toArray());

        // Find actor in LPA
        $resolvedActor = ($this->findActorInLpa)($lpa, $matchData);

        // We may want to turn off the ability for a user to have their case pushed to the cleansing
        // team if they fail to match and have a "newer" older lpa. In which case they'll be told we
        // can't find their LPA.
        ($this->restrictSendingLpaForCleansing)($lpa->toArray(), $resolvedActor);

        if ($resolvedActor === null) {
            $this->logger->info(
                'Actor details for LPA {uId} not found',
                [
                    'uId' => $matchData['reference_number'],
                ]
            );
            throw new BadRequestException(
                'LPA details do not match',
                ['lpaRegDate' => $lpa['registrationDate']]
            );
        }

        $response = new AccessForAllValidation(
            $resolvedActor,
            $lpa,
            $lpaAddedData['lpaActorToken'] ?? null
        );

        // Checks if the actor already has an active activation key or has requested one. If forced ignore
        if (!$matchData['force_activation_key']) {
            $this->processActivationCode($userId, $matchData['reference_number'], $response, $lpaAddedData);
        }

        return $response;
    }
}
