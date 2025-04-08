<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Entity\Value\LpaUid;
use App\Exception\ApiException;
use App\Exception\NotFoundException;
use Exception;
use Psr\Log\LoggerInterface;

class RemoveLpa
{
    public function __construct(
        private UserLpaActorMapInterface $userLpaActorMapRepository,
        private LpaManagerInterface $lpaManager,
        private ViewerCodesInterface $viewerCodesRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Removes actor association from all viewer codes created by a user
     * and removes an LPA from a users account
     *
     * @param string $userId The user account ID that must correlate to the $token
     * @param string $token  UserLpaActorToken that map an LPA to a user account
     * @return array A structure that contains processed LPA data and metadata
     * @throws NotFoundException|Exception
     */
    public function __invoke(string $userId, string $token): array
    {
        $userActorLpa = $this->userLpaActorMapRepository->get($token);

        if (is_null($userActorLpa)) {
            $this->logger->notice(
                'User actor lpa record not found for actor token {Id}',
                ['Id' => $token]
            );
            throw new NotFoundException('User actor lpa record not found for actor token - ' . $token);
        }

        // Ensure the passed userId matches the passed token
        if ($userId !== $userActorLpa['UserId']) {
            $this->logger->notice(
                'User Id {userId} passed does not match the user in userActorLpaMap for actor token {actorToken}',
                ['userId' => $userId, 'actorToken' => $token]
            );
            throw new NotFoundException(
                'User Id passed does not match the user in userActorLpaMap for token - ' . $token
            );
        }

        $viewerCodes = $this->getListOfViewerCodesToBeUpdated($userActorLpa);

        if (!empty($viewerCodes)) {
            foreach ($viewerCodes as $viewerCodeRecord) {
                $codeOwner = $this->userLpaActorMapRepository->get($viewerCodeRecord['UserLpaActor']);

                if (is_null($codeOwner)) {
                    $this->logger->notice(
                        'User actor lpa record not found for actor token {Id}',
                        ['Id' => $viewerCodeRecord['UserLpaActor']]
                    );
                    throw new NotFoundException(
                        'User actor lpa record not found for actor token - ' . $viewerCodeRecord['UserLpaActor']
                    );
                }

                //Actor id in codeOwner array is an int in the case of an old lpa, however is a string in the case of a M-lpa
                $this->viewerCodesRepository->removeActorAssociation(
                    $viewerCodeRecord['ViewerCode'],
                    (string)$codeOwner['ActorId'],
                );
            }
        }

        // get the LPA to display the donor name and lpa type in the flash message
        // we don't use getByUserLpaActorToken as it returns null if actor is inactive

        $uid            = isset($userActorLpa['SiriusUid']) ? $userActorLpa['SiriusUid'] : $userActorLpa['LpaUid'];
        $lpaRemovedData = $this->lpaManager->getByUid($uid)->getData();

        $deletedData = $this->userLpaActorMapRepository->delete($token);

        if ($deletedData['Id'] !== $userActorLpa['Id']) {
            $this->logger->notice(
                'Incorrect data deleted from UserLpaActorMap. Expected deletion of data
                for UserLpaActorId {expectedId}, actual deletion of data for UserLpaActorId {deletedId}',
                [
                    'expectedId' => $deletedData['Id'],
                    'deletedId'  => $userActorLpa['Id'],
                ]
            );
            throw new ApiException('Incorrect LPA data deleted from users account');
        }

        $lpaDonorData = $lpaRemovedData->getDonor();
        $response = [
            'donor' => [
                'uId'         => $lpaDonorData->getUid(),
                'firstname'   => $lpaDonorData->getFirstnames(),
                'middlenames' => isset($lpaDonorData->middlenames) ? $lpaDonorData->getMiddleNames() : '',
                'surname'     => $lpaDonorData->getSurname(),
            ],
            'caseSubtype'     => $lpaRemovedData->getCaseSubType(),
        ];

        return $response;
    }

    private function getListOfViewerCodesToBeUpdated(array $userActorLpa): ?array
    {
        $uid = isset($userActorLpa['SiriusUid']) ? $userActorLpa['SiriusUid'] : $userActorLpa['LpaUid'];
        $siriusUid = new LpaUid($uid);

        //Lookup records in ViewerCodes table using siriusUid
        $viewerCodesData = $this->viewerCodesRepository->getCodesByLpaId($siriusUid);
        foreach ($viewerCodesData as $viewerCodeRecord) {
            if (
                isset($viewerCodeRecord['UserLpaActor'])
                && ($viewerCodeRecord['UserLpaActor'] === $userActorLpa['Id'])
            ) {
                $viewerCodes[] = $viewerCodeRecord;
            }
        }
        return $viewerCodes ?? [];
    }
}
