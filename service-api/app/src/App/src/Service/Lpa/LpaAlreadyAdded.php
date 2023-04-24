<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Service\Features\FeatureEnabled;
use Psr\Log\LoggerInterface;

class LpaAlreadyAdded
{
    /**
     * @codeCoverageIgnore
     * @param LpaService               $lpaService
     * @param UserLpaActorMapInterface $userLpaActorMapRepository
     * @param FeatureEnabled           $featureEnabled
     * @param LoggerInterface          $logger
     */
    public function __construct(
        private LpaService $lpaService,
        private UserLpaActorMapInterface $userLpaActorMapRepository,
        private FeatureEnabled $featureEnabled,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $userId
     * @param string $lpaUid
     * @return array|null
     */
    public function __invoke(string $userId, string $lpaUid): ?array
    {
        if (($this->featureEnabled)('save_older_lpa_requests')) {
            return $this->saveOfRequestFeature($userId, $lpaUid);
        } else {
            return $this->preSaveOfRequestFeature($userId, $lpaUid);
        }
    }

    /**
     * @param array  $record
     * @param string $userId
     * @return array|null
     */
    private function populateLpaRecord(array $record, string $userId): ?array
    {
        $lpa = $this->lpaService->getByUserLpaActorToken($record['Id'], $userId);

        if (empty($lpa)) {
            return null;
        }

        $response = [
            'donor'                => [
                'uId'         => $lpa['lpa']['donor']['uId'],
                'firstname'   => $lpa['lpa']['donor']['firstname'],
                'middlenames' => $lpa['lpa']['donor']['middlenames'],
                'surname'     => $lpa['lpa']['donor']['surname'],
            ],
            'caseSubtype'          => $lpa['lpa']['caseSubtype'],
            'lpaActorToken'        => $record['Id'],
            'activationKeyDueDate' => $lpa['activationKeyDueDate'] ?? null,
        ];

        if (array_key_exists('ActivateBy', $record)) {
            $response['notActivated'] = true;
        }

        return $response;
    }

    /**
     * @param string $userId
     * @param string $lpaUid
     * @return array|null
     */
    private function preSaveOfRequestFeature(string $userId, string $lpaUid): ?array
    {
        $lpasAdded = $this->lpaService->getAllLpasAndRequestsForUser($userId);

        foreach ($lpasAdded as $userLpaActorToken => $lpaData) {
            if ($lpaData['lpa']['uId'] === $lpaUid) {
                $this->logger->info(
                    'Account with Id {id} has attempted to add LPA {uId} which already exists in their account',
                    [
                        'id'  => $userId,
                        'uId' => $lpaUid,
                    ]
                );
                return [
                    'donor'         => [
                        'uId'         => $lpaData['lpa']['donor']['uId'],
                        'firstname'   => $lpaData['lpa']['donor']['firstname'],
                        'middlenames' => $lpaData['lpa']['donor']['middlenames'],
                        'surname'     => $lpaData['lpa']['donor']['surname'],
                    ],
                    'caseSubtype'   => $lpaData['lpa']['caseSubtype'],
                    'lpaActorToken' => $userLpaActorToken,
                ];
            }
        }

        return null;
    }

    /**
     * @param string $userId
     * @param string $lpaUid
     * @return array|null
     */
    private function saveOfRequestFeature(string $userId, string $lpaUid): ?array
    {
        $savedLpaRecords = $this->userLpaActorMapRepository->getByUserId($userId);

        foreach ($savedLpaRecords as $record) {
            if ($record['SiriusUid'] === $lpaUid) {
                return $this->populateLpaRecord($record, $userId);
            }
        }

        return null;
    }
}
