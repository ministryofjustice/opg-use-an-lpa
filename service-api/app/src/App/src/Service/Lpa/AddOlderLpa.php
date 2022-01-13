<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use DateTime;
use Psr\Log\LoggerInterface;
use App\Service\Features\FeatureEnabled;

class AddOlderLpa
{
    private FindActorInLpa $findActorInLpa;

    private LpaAlreadyAdded $lpaAlreadyAdded;
    private LpaService $lpaService;
    private OlderLpaService $olderLpaService;
    private ValidateOlderLpaRequirements $validateOlderLpaRequirements;
    private RestrictSendingLpaForCleansing $restrictSendingLpaForCleansing;
    private LoggerInterface $logger;
    private FeatureEnabled $featureEnabled;


    /**
     * @param FindActorInLpa               $findActorInLpa
     * @param LpaService                   $lpaService
     * @param LpaAlreadyAdded              $lpaAlreadyAdded
     * @param ValidateOlderLpaRequirements $validateOlderLpaRequirements
     * @param LoggerInterface              $logger
     * @param OlderLpaService              $olderLpaService
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        FindActorInLpa $findActorInLpa,
        LpaService $lpaService,
        LpaAlreadyAdded $lpaAlreadyAdded,
        OlderLpaService $olderLpaService,
        ValidateOlderLpaRequirements $validateOlderLpaRequirements,
        RestrictSendingLpaForCleansing $restrictSendingLpaForCleansing,
        LoggerInterface $logger,
        FeatureEnabled $featureEnabled
    ) {
        $this->findActorInLpa = $findActorInLpa;
        $this->lpaService = $lpaService;
        $this->lpaAlreadyAdded = $lpaAlreadyAdded;
        $this->olderLpaService = $olderLpaService;
        $this->validateOlderLpaRequirements = $validateOlderLpaRequirements;
        $this->restrictSendingLpaForCleansing = $restrictSendingLpaForCleansing;
        $this->logger = $logger;
        $this->featureEnabled = $featureEnabled;
    }

    /**
     * Given a users ID, and an array containing an LPA reference number alongside some data that may, or may not
     * match an active actor within that LPA return a data array containing details about that LPA - otherwise
     * throw an exception that details why the match was not able to be made.
     *
     * @param string $userId The users database ID
     * @param array  $matchData The user supplied information to attempt to match an LPA to
     *
     * @return array
     *
     * @throws BadRequestException|NotFoundException
     */
    public function validateRequest(string $userId, array $matchData): array
    {
        // Check if it's been added to the users account already
        $lpaAddedData = ($this->lpaAlreadyAdded)($userId, (string) $matchData['reference_number']);

        if ($lpaAddedData !== null) {
            if (!array_key_exists('notActivated', $lpaAddedData)) {
                $this->logger->notice(
                    'User {id} attempted to request a key for the LPA {uId} which already exists in their account',
                    [
                        'id' => $userId,
                        'uId' => $matchData['reference_number'],
                    ]
                );
                throw new BadRequestException('LPA already added', $lpaAddedData);
            }
        }

        // Fetch the LPA from the LpaServiceOLDER_LPA_KEY_ALREADY_REQUESTED
        $lpa = $this->lpaService->getByUid((string) $matchData['reference_number']);
        if ($lpa === null) {
            $this->logger->info(
                'The LPA {uId} entered by user is not found in Sirius',
                [
                    'uId' => (string) $matchData['reference_number'],
                ]
            );
            throw new NotFoundException('LPA not found');
        }

        $lpaData = $lpa->getData();

        // Ensure LPA meets our registration requirements
        ($this->validateOlderLpaRequirements)($lpaData);

        // Find actor in LPA
        $resolvedActor = ($this->findActorInLpa)($lpaData, $matchData);

        // We may want to turn off the ability for a user to have their case pushed to the cleansing
        // team if they fail to match and have a "newer" older lpa. In which case they'll be told we
        // can't find their LPA.
        if (($this->featureEnabled)('dont_send_lpas_registered_after_sep_2019_to_cleansing_team')) {
            ($this->restrictSendingLpaForCleansing)($lpaData, $resolvedActor);
        }

        if ($resolvedActor === null) {
            $this->logger->info(
                'Actor details for LPA {uId} not found',
                [
                    'uId' => $matchData['reference_number'],
                ]
            );
            throw new BadRequestException(
                'LPA details do not match',
                ['lpaRegDate' => $lpaData['registrationDate']]
            );
        }

        // Attach donor/attorney data to be used by the front
        if ($resolvedActor['role'] === 'attorney') {
            $resolvedActor['attorney'] = [
                'uId'           => $resolvedActor['actor']['uId'],
                'firstname'     => $resolvedActor['actor']['firstname'],
                'middlenames'   => $resolvedActor['actor']['middlenames'],
                'surname'       => $resolvedActor['actor']['surname']
            ];
        }

        if ($token = $lpaAddedData['lpaActorToken'] ?? null) {
            $resolvedActor['lpaActorToken'] = $token;
        }

        $resolvedActor['caseSubtype'] = $lpaData['caseSubtype'];
        $resolvedActor['donor'] = [
            'uId'           => $lpaData['donor']['uId'],
            'firstname'     => $lpaData['donor']['firstname'],
            'middlenames'   => $lpaData['donor']['middlenames'],
            'surname'       => $lpaData['donor']['surname'],
        ];

        // Checks if the actor already has an active activation key or has requested one. If forced ignore
        if (!$matchData['force_activation_key']) {
            if (isset($lpaAddedData['notActivated'])) { // array_key_exists breaks if $lpaAddedData is null
                $this->logger->notice(
                    'User {id} attempted to request a key for the LPA {uId} which they have already requested',
                    [
                        'id' => $userId,
                        'uId' => $matchData['reference_number'],
                    ]
                );
                throw new BadRequestException(
                    'Activation key already requested for LPA',
                    [
                        'donor'                => $lpaAddedData['donor'],
                        'caseSubtype'          => $lpaAddedData['caseSubtype'],
                        'activationKeyDueDate' => $lpaAddedData['activationKeyDueDate']
                    ]
                );
            }

            $hasActivationCode = $this->olderLpaService->hasActivationCode(
                $resolvedActor['lpa-id'],
                $resolvedActor['actor']['uId']
            );

            if ($hasActivationCode instanceof DateTime) {
                throw new BadRequestException(
                    'LPA has an activation key already',
                    [
                        'donor'         => $resolvedActor['donor'],
                        'caseSubtype'   => $resolvedActor['caseSubtype'],
                        'activationKeyDueDate' => date(
                            'Y-m-d',
                            strtotime($hasActivationCode->format('c') . ' + 10 days')
                        ),
                    ]
                );
            }
        }

        return $resolvedActor;
    }
}
