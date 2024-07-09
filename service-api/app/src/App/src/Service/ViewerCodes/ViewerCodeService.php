<?php

declare(strict_types=1);

namespace App\Service\ViewerCodes;

use Exception;
use App\DataAccess\Repository\{KeyCollisionException,
    UserLpaActorMapInterface,
    ViewerCodeActivityInterface,
    ViewerCodesInterface
};
use DateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type ViewerCode from ViewerCodesInterface
 */
class ViewerCodeService
{
    public function __construct(
        private ViewerCodesInterface $viewerCodesRepository,
        private ViewerCodeActivityInterface $viewerCodeActivityRepository,
        private UserLpaActorMapInterface $userLpaActorMapRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $token
     * @param string $userId
     * @param string $organisation
     * @psalm-return ViewerCode
     * @return array|null
     * @throws Exception
     */
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
                    $map['ActorId']
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

    /**
     * @param string $token
     * @param string $userId
     * @psalm-return ViewerCode[]
     * @return array|null
     */
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

    /**
     * @param array $codes
     * @return array
     */
    private function populateCodeStatuses(array $codes): array
    {
        $viewerCodesAndStatuses = $this->viewerCodeActivityRepository->getStatusesForViewerCodes($codes);

        /* Get the actor id for the respective viewer code from either CreatedBy or using UserLpaActor
           A viewer record will have either UserLpaActor data or CreatedBy data.
           Around 49 viewer code records missing both and will not be able to show the code creator name on UI
        */
        foreach ($viewerCodesAndStatuses as $key => $viewerCode) {
            if (empty($viewerCode['UserLpaActor'])) {
                if (!empty($viewerCode['CreatedBy'])) {
                    $viewerCodesAndStatuses[$key]['ActorId'] = $viewerCode['CreatedBy'];
                }
            } else {
                $codeOwner = $this->getCodeOwner($viewerCode['UserLpaActor']);
                if ($codeOwner !== null) {
                    $viewerCodesAndStatuses[$key]['ActorId'] = $codeOwner['ActorId'];
                }
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
