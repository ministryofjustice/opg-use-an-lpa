<?php

declare(strict_types=1);

namespace App\Service\Lpa\AddLpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use App\Service\Lpa\LpaManagerInterface;
use App\Value\LpaUid;
use DateTimeInterface;

class LpaAlreadyAdded
{
    /**
     * @codeCoverageIgnore
     * @param LpaManagerInterface      $lpaManager
     * @param UserLpaActorMapInterface $userLpaActorMapRepository
     */
    public function __construct(
        private LpaManagerInterface $lpaManager,
        private UserLpaActorMapInterface $userLpaActorMapRepository,
    ) {
    }

    /**
     * @throws ApiException
     */
    public function __invoke(string $userId, LpaUid $lpaUid): ?array
    {
        $savedLpaRecords = $this->userLpaActorMapRepository->getByUserId($userId);

        foreach ($savedLpaRecords as $record) {
            if (($record['SiriusUid'] ?? $record['LpaUid'] ?? 'ERROR') === $lpaUid->getLpaUid()) {
                return $this->populateLpaRecord($record, $userId);
            }
        }

        return null;
    }

    /**
     * @param array  $record
     * @param string $userId
     * @return array{
     *      donor: array{
     *          uId: string,
     *          firstnames: string,
     *          surname: string,
     *      },
     *      caseSubtype: string,
     *      lpaActorToken: string,
     *      activationKeyDueDate?: DateTimeInterface,
     *      notActivated?: bool,
     *  }|null
     * @throws ApiException
     */
    private function populateLpaRecord(array $record, string $userId): ?array
    {
        $lpa = $this->lpaManager->getByUserLpaActorToken($record['Id'], $userId);

        if (empty($lpa)) {
            return null;
        }

        $donorDetails = $lpa->lpa->getDonor();

        $response = [
            'donor'         => [
                'uId'        => $donorDetails->getUid(),
                'firstnames' => $donorDetails->getFirstnames(),
                'surname'    => $donorDetails->getSurname(),
            ],
            'caseSubtype'   => $lpa->lpa->getCaseSubtype(),
            'lpaActorToken' => $record['Id'],
        ];

        if (isset($lpa->activationKeyDueDate)) {
            $response['activationKeyDueDate'] = $lpa->activationKeyDueDate;
        }

        if (array_key_exists('ActivateBy', $record)) {
            $response['notActivated'] = true;
        }

        return $response;
    }
}
