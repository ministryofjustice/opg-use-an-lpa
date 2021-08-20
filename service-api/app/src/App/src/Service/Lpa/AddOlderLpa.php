<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

class AddOlderLpa
{
    private FindActorInLpa $findActorInLpa;
    private LoggerInterface $logger;
    private LpaAlreadyAdded $lpaAlreadyAdded;
    private LpaService $lpaService;
    private ValidateOlderLpaRequirements $validateOlderLpaRequirements;

    /**
     * @param FindActorInLpa               $findActorInLpa
     * @param LpaService                   $lpaService
     * @param LpaAlreadyAdded              $lpaAlreadyAdded
     * @param ValidateOlderLpaRequirements $validateOlderLpaRequirements
     * @param LoggerInterface              $logger
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        FindActorInLpa $findActorInLpa,
        LpaService $lpaService,
        LpaAlreadyAdded $lpaAlreadyAdded,
        ValidateOlderLpaRequirements $validateOlderLpaRequirements,
        LoggerInterface $logger
    )
    {
        $this->findActorInLpa = $findActorInLpa;
        $this->lpaService = $lpaService;
        $this->lpaAlreadyAdded = $lpaAlreadyAdded;
        $this->validateOlderLpaRequirements = $validateOlderLpaRequirements;
        $this->logger = $logger;
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
        if (null !== $lpaAddedData = ($this->lpaAlreadyAdded)($userId, (string) $matchData['reference_number'])) {
            $this->logger->notice(
                'User {id} attempted to request a key for the LPA {uId} which already exists in their account',
                [
                    'id' => $userId,
                    'uId' => $matchData['reference_number'],
                ]
            );
            throw new BadRequestException('LPA already added', $lpaAddedData);
        }

        // Fetch the LPA from the LpaService
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
        if (!($this->validateOlderLpaRequirements)($lpaData)) {
            throw new BadRequestException('LPA not eligible due to registration date');
        }

        // Find actor in LPA
        $resolvedActor = ($this->findActorInLpa)($lpaData, $matchData);
        if ($resolvedActor === null) {
            $this->logger->info(
                'Actor details for LPA {uId} not found',
                [
                    'uId' => $matchData['reference_number'],
                ]
            );
            throw new BadRequestException('LPA details do not match');
        }

        $resolvedActor['caseSubtype'] = $lpaData['caseSubtype'];
        $resolvedActor['donor'] = [
            'uId'           => $lpaData['donor']['uId'],
            'firstname'     => $lpaData['donor']['firstname'],
            'middlenames'   => $lpaData['donor']['middlenames'],
            'surname'       => $lpaData['donor']['surname'],
        ];

        return $resolvedActor;
    }
}
