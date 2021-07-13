<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;

class LpaAlreadyAdded
{
    private LpaService $lpaService;

    private LoggerInterface $logger;

    /**
     * LpaAlreadyAdded constructor.
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
        $lpasAdded = $this->lpaService->getAllForUser($userId);

        foreach ($lpasAdded as $userLpaActorToken => $lpaData) {
            if ($lpaData['lpa']['uId'] === $lpaUid) {
                $this->logger->info(
                    'Account with Id {id} has attempted to add LPA {uId} which already exists in their account',
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
                    'lpaActorToken' => $userLpaActorToken
                ];
            }
        }
        return null;
    }
}
