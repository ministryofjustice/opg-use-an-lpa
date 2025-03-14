<?php

declare(strict_types=1);

namespace App\Service\Lpa\AddLpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Service\Lpa\LpaManagerInterface;

class LpaAlreadyAdded
{
    /**
     * @codeCoverageIgnore
     * @param LpaManagerInterface      $lpaManager
     * @param UserLpaActorMapInterface $userLpaActorMapRepository
     */
    public function __construct(
        private LpaManagerInterface $lpaManager,
        private UserLpaActorMapInterface $userLpaActorMapRepository
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
            if (($record['SiriusUid'] ?? 'ERROR') === $lpaUid) {
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
        $lpa = $this->lpaManager->getByUserLpaActorToken($record['Id'], $userId);

        if (empty($lpa)) {
            return null;
        }

        $donorDetails = $lpa['lpa']->getDonor();

        $response = [
            'donor'                => [
                'uId'         => $donorDetails->getUid(),
                'firstname'   => $donorDetails->getFirstnames(),
                'middlenames' => $donorDetails->getMiddleNames(),
                'surname'     => $donorDetails->getSurname(),
            ],
            'caseSubtype'          => $lpa['lpa']->getCaseSubtype(),
            'lpaActorToken'        => $record['Id'],
            'activationKeyDueDate' => $lpa['activationKeyDueDate'] ?? null,
        ];

        if (array_key_exists('ActivateBy', $record)) {
            $response['notActivated'] = true;
        }

        return $response;
    }
}
