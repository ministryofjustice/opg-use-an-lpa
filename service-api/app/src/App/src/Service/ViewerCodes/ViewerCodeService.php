<?php

declare(strict_types=1);

namespace App\Service\ViewerCodes;

use App\DataAccess\Repository;
use App\Service\Lpa\LpaService;
use DateTime;
use DateTimeZone;

class ViewerCodeService
{
    /**
     * @var Repository\ViewerCodesInterface
     */
    private $viewerCodesRepository;

    /**
     * @var Repository\UserLpaActorMapInterface
     */
    private $userLpaActorMapRepository;

    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * ViewerCodeService constructor.
     * @param Repository\ViewerCodesInterface $viewerCodesRepository
     * @param Repository\UserLpaActorMapInterface $userLpaActorMapRepository
     * @param LpaService $lpaService
     */
    public function __construct(
        Repository\ViewerCodesInterface $viewerCodesRepository,
        Repository\UserLpaActorMapInterface $userLpaActorMapRepository,
        LpaService $lpaService
    )
    {
        $this->lpaService = $lpaService;
        $this->viewerCodesRepository = $viewerCodesRepository;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
    }

    public function addCode(string $token, string $userId, string $organisation) : ?array
    {
        $map = $this->userLpaActorMapRepository->get($token);

        // Ensure the passed userId matches the passed token
        if ($userId !== $map['UserId']) {
            return null;
        }

        //---

        $expires = new DateTime(
            '23:59:59 +30 days',                    // Set to the last moment of the day, x days from now.
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
            } catch (Repository\KeyCollisionException $e) {
                // Allows the loop to repeat with a new code.
            }

        } while(!$added);

        return [
            'code' => $code,
            'expires' => $expires->format('c'),
            'organisation' => $organisation,
        ];
    }

    public function getCodes(string $token, string $userId)
    {
        $map = $this->userLpaActorMapRepository->get($token);

        // Ensure the passed userId matches the passed token
        if ($userId !== $map['UserId']) {
            return null;
        }

        $accessCodes = $this->viewerCodesRepository->getCodesByUserLpaActorId($token);

        return $accessCodes;
    }

}
