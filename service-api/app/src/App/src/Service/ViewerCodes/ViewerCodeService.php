<?php

declare(strict_types=1);

namespace App\Service\ViewerCodes;

use App\DataAccess\Repository\{KeyCollisionException, UserLpaActorMapInterface, ViewerCodesInterface};
use App\Service\Lpa\LpaService;
use DateTime;
use DateTimeZone;

class ViewerCodeService
{
    private ViewerCodesInterface $viewerCodesRepository;

    private UserLpaActorMapInterface $userLpaActorMapRepository;

    /**
     * @var LpaService
     */
    private LpaService $lpaService;

    /**
     * ViewerCodeService constructor.
     * @param ViewerCodesInterface $viewerCodesRepository
     * @param UserLpaActorMapInterface $userLpaActorMapRepository
     * @param LpaService $lpaService
     */
    public function __construct(
        ViewerCodesInterface $viewerCodesRepository,
        UserLpaActorMapInterface $userLpaActorMapRepository,
        LpaService $lpaService
    ) {
        $this->lpaService = $lpaService;
        $this->viewerCodesRepository = $viewerCodesRepository;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
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
            '23:59:59 +30 days',                   // Set to the last moment of the day, x days from now.
            new DateTimeZone('Europe/London')   // Ensures we compensate for GMT vs BST.
        );

        //---

        $code = null;

        do {
            $added = false;

            $code = CodeGenerator::generateCode();

            try {
                $this->viewerCodesRepository->add(
                    $code,
                    $map['Id'],
                    $map['SiriusUid'],
                    $expires,
                    $organisation
                );

                $added = true;
            } catch (KeyCollisionException $e) {
                // Allows the loop to repeat with a new code.
            }
        } while (!$added);

        return [
            'code' => $code,
            'expires' => $expires->format('c'),
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

        return $this->viewerCodesRepository->getCodesByLpaId($siriusUid);
    }

    /**
     * Cancels a access code initiated by the actor
     *
     * @param string $userLpaActorToken
     * @param string $userId
     * @param string $code
     */
    public function cancelCode(string $userLpaActorToken, string $userId, string $code): void
    {
        $map = $this->userLpaActorMapRepository->get($userLpaActorToken);

        // Ensure the passed userId matches the passed token
        if ($userId !== $map['UserId']) {
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
}
