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

                $this->viewerCodesRepository->removeActorAssociation(
                    $viewerCodeRecord['ViewerCode'],
                    $codeOwner['ActorId'],
                );
            }
        }

        // get the LPA to display the donor name and lpa type in the flash message
        // we don't use getByUserLpaActorToken as it returns null if actor is inactive
        $lpaRemovedData = $this->lpaManager->getByUid($userActorLpa['SiriusUid'])->getData();

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

        // TODO UML-3606 this will always be an object at this time but we need to make it check for existing
        //      tests to pass
        return $lpaRemovedData instanceof SiriusLpa ? $lpaRemovedData->toArray() : $lpaRemovedData;
    }

    private function getListOfViewerCodesToBeUpdated(array $userActorLpa): ?array
    {
        $siriusUid = new LpaUid($userActorLpa['SiriusUid']);

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
