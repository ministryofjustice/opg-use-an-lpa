<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;

class LpaAlreadyAdded
{
    /**
     * @codeCoverageIgnore
     * @param LpaService               $lpaService
     * @param UserLpaActorMapInterface $userLpaActorMapRepository
     */
    public function __construct(
        private LpaService $lpaService,
        private UserLpaActorMapInterface $userLpaActorMapRepository,
    ) {
    }

    /**
     * @param string $userId
     * @param string $lpaUid
     * @return array|null
     */
    public function __invoke(string $userId, string $lpaUid): ?array
    {
        $savedLpaRecords = $this->userLpaActorMapRepository->getByUserId($userId);

        foreach ($savedLpaRecords as $record) {
            if ($record['SiriusUid'] === $lpaUid) {
                return $this->populateLpaRecord($record, $userId);
            }
        }

        return null;
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
}
