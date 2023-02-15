<?php

declare(strict_types=1);

namespace App\Service\ViewerCodes;

use App\DataAccess\Repository\{KeyCollisionException,
    UserLpaActorMapInterface,
    ViewerCodeActivityInterface,
    ViewerCodesInterface};
use DateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;

class ViewerCodeService
{
    public function __construct(
        private ViewerCodesInterface $viewerCodesRepository,
        private ViewerCodeActivityInterface $viewerCodeActivityRepository,
        private UserLpaActorMapInterface $userLpaActorMapRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function addCode(string $token, string $userId, string $organisation): ?array
    {
        $map = $this->userLpaActorMapRepository->get($token);

        // Ensure the passed userId matches the passed token
        if ($userId !== $map['UserId']) {
            return null;
        }

        //---

        $expires = new DateTime(
            '23:59:59 +30 days',              // Set to the last moment of the day, x days from now.
            new DateTimeZone('Europe/London') // Ensures we compensate for GMT vs BST.
        );

        do {
            $added = false;

            $code = CodeGenerator::generateCode();

            try {
                $this->viewerCodesRepository->add(
                    $code,
                    $map['Id'],
                    $map['SiriusUid'],
                    $expires,
                    $organisation,
                    (string)$map['ActorId']
                );

                $added = true;
            } catch (KeyCollisionException) {
                // Allows the loop to repeat with a new code.
            }
        } while (!$added);

        return [
            'code'         => $code,
            'expires'      => $expires->format('c'),
            'organisation' => $organisation,
        ];
    }

    public function getCodes(string $token, string $userId): ?array
    {
        $map = $this->userLpaActorMapRepository->get($token);

        // Ensure the passed userId matches the passed token
        if ($userId !== $map['UserId']) {
            return null;
        }

        $siriusUid = $map['SiriusUid'];

        $codes = $this->viewerCodesRepository->getCodesByLpaId($siriusUid);

        if (!empty($codes)) {
            $codes = $this->populateCodeStatuses($codes);
        }

        return $codes;
    }

    /**
     * Cancels an access code initiated by the actor
     *
     * @param string $userLpaActorToken
     * @param string $userId
     * @param string $code
     */
    public function cancelCode(string $userLpaActorToken, string $userId, string $code): void
    {
        $map = $this->userLpaActorMapRepository->get($userLpaActorToken);

        // Ensure the passed userId matches the passed token
        if (empty($map['UserId']) || $userId !== $map['UserId']) {
            return;
        }

        if ($this->viewerCodesRepository->get($code) === null) {
            return;
        }

        $this->viewerCodesRepository->cancel(
            $code,
            new DateTime()
        );
    }

    private function populateCodeStatuses(array $codes): array
    {
        $viewerCodesAndStatuses = $this->viewerCodeActivityRepository->getStatusesForViewerCodes($codes);

        // Get the actor id for the respective viewer code by UserLpaActor
        foreach ($viewerCodesAndStatuses as $key => $viewerCode) {
            if (empty($viewerCode['UserLpaActor'])) {
                $viewerCodesAndStatuses[$key]['ActorId'] = $viewerCode['CreatedBy'];
                continue;
            }

            $codeOwner = $this->getCodeOwner($viewerCode['UserLpaActor']);

            if ($codeOwner !== null) {
                $viewerCodesAndStatuses[$key]['ActorId'] = $codeOwner['ActorId'];
            }
        }

        return $viewerCodesAndStatuses;
    }

    private function getCodeOwner(string $userLpaActor): ?array
    {
        $codeOwner = $this->userLpaActorMapRepository->get($userLpaActor);

        if ($codeOwner === null) {
            $this->logger->error(
                'Code owner was not fetched for LPA with UserActorLpaToken {token}',
                [
                    'token' => $userLpaActor,
                ]
            );
        }

        return $codeOwner;
    }
}
