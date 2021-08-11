<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;

class ActivationKeyAlreadyRequested
{
    private LpaService $lpaService;

    private LoggerInterface $logger;

    /**
     * ActivationKeyAlreadyRequested constructor.
     *
     * @param LpaService               $lpaService
     * @param LoggerInterface          $logger
     */
    public function __construct(
        LpaService $lpaService,
        LoggerInterface $logger
    ) {
        $this->lpaService = $lpaService;
        $this->logger = $logger;
    }

    /**
     * @param string $userId
     * @param string $lpaUid
     *
     * @return array|null
     */
    public function __invoke(string $userId, string $lpaUid): ?array
    {
        $activeActivationKeyCheck = true;
        $lpasAdded = $this->lpaService->getAllForUser($userId, $activeActivationKeyCheck);

        foreach ($lpasAdded as $userLpaActorToken => $lpaData) {
            if ($lpaData['lpa']['uId'] === $lpaUid) {
                $this->logger->info(
                    'User {id} has has already an active activation key for the  LPA {uId}',
                    [
                        'id' => $userId,
                        'uId' => $lpaUid
                    ]
                );
                return [
                    'donor'         => [
                        'uId'           => $lpaData['lpa']['donor']['uId'],
                        'firstname'     => $lpaData['lpa']['donor']['firstname'],
                        'middlenames'   => $lpaData['lpa']['donor']['middlenames'],
                        'surname'       => $lpaData['lpa']['donor']['surname'],
                    ],
                    'caseSubtype'   => $lpaData['lpa']['caseSubtype'],
                ];
            }
        }
        return null;
    }
}
