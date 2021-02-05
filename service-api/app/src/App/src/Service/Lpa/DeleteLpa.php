<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

class DeleteLpa
{
    private LoggerInterface $logger;
    private UserLpaActorMapInterface $userLpaActorMapRepository;
    private ViewerCodesInterface $viewerCodesRepository;

    public function __construct(
        LoggerInterface $logger,
        UserLpaActorMapInterface $userLpaActorMapRepository,
        ViewerCodesInterface $viewerCodesRepository
    ) {
        $this->logger = $logger;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
        $this->viewerCodesRepository = $viewerCodesRepository;
    }

    /**
     * Deletes an LPA from a users account
     *
     * @param string $userId The user account ID that must correlate to the $token
     * @param string $token UserLpaActorToken that map an LPA to a user account
     * @return ?array A structure that contains processed LPA data and metadata
     */
    public function __invoke(string $userId, string $token): ?array
    {
        $userActorLpa = $this->userLpaActorMapRepository->get($token);

        if (is_null($userActorLpa)) {
            $this->logger->notice(
                'User actor lpa record  not found for actor token {Id}',
                ['Id' => $token]
            );
            throw new NotFoundException('User actor lpa record  not found for actor token - ' . $token);
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

        //Get list of viewer codes to be updated
        $viewerCodes = $this->getListOfViewerCodesToBeUpdated($userActorLpa);

        //Update query to remove actor association in viewer code table
        if (!empty($viewerCodes)) {
            foreach ($viewerCodes as $key => $viewerCode) {
                $this->viewerCodesRepository->removeActorAssociation($viewerCode);
            }
        }

        return $this->userLpaActorMapRepository->delete($token);
    }

    private function getListOfViewerCodesToBeUpdated(array $userActorLpa): ?array
    {
        $siriusUid = $userActorLpa['SiriusUid'];

        //Lookup records in ViewerCodes table using siriusUid
        $viewerCodesData = $this->viewerCodesRepository->getCodesByLpaId($siriusUid);
        foreach ($viewerCodesData as $key => $viewerCodeRecord) {
            if (
                isset($viewerCodeRecord['UserLpaActor'])
                && ($viewerCodeRecord['UserLpaActor'] === $userActorLpa['Id'])
            ) {
                $viewerCodes[] = $viewerCodeRecord['ViewerCode'];
            }
        }
        return $viewerCodes;
    }
}
