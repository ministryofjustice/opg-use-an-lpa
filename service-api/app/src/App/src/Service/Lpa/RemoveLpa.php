<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Exception\NotFoundException;
use DateTime;
use Psr\Log\LoggerInterface;

class RemoveLpa
{
    private LoggerInterface $logger;
    private UserLpaActorMapInterface $userLpaActorMapRepository;
    private ViewerCodesInterface $viewerCodesRepository;
    private LpaService $lpaService;

    public function __construct(
        LoggerInterface $logger,
        UserLpaActorMapInterface $userLpaActorMapRepository,
        ViewerCodesInterface $viewerCodesRepository,
        LpaService $lpaService
    ) {
        $this->logger = $logger;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
        $this->viewerCodesRepository = $viewerCodesRepository;
        $this->lpaService = $lpaService;
    }

    /**
     * Removes actor association from all viewer codes created by a user
     * and removes an LPA from a users account
     *
     * @param string $userId The user account ID that must correlate to the $token
     * @param string $token UserLpaActorToken that map an LPA to a user account
     *
     * @return array A structure that contains processed LPA data and metadata
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
                $this->viewerCodesRepository->removeActorAssociation($viewerCodeRecord['ViewerCode']);
                if (
                    // only cancel active codes
                    !array_key_exists('Cancelled', $viewerCodeRecord) &&
                    new DateTime($viewerCodeRecord['Expires']) >= new DateTime('now')
                ) {
                    $this->viewerCodesRepository->cancel($viewerCodeRecord['ViewerCode'], new DateTime());
                }
            }
        }

        // get the LPA to display the donor name and lpa type in the flash message
        // we don't use getByUserLpaActorToken as it returns null if actor is inactive
        $lpaRemovedData = $this->lpaService->getByUid($userActorLpa['SiriusUid'])->getData();

        $this->userLpaActorMapRepository->delete($token);

        return $lpaRemovedData;
    }

    private function getListOfViewerCodesToBeUpdated(array $userActorLpa): ?array
    {
        $siriusUid = $userActorLpa['SiriusUid'];

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
        return ($viewerCodes ?? []);
    }
}
