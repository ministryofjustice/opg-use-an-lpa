<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use ArrayObject;
use Psr\Log\LoggerInterface;

class LpaAlreadyAdded
{
    private LpaService $lpaService;

    private LoggerInterface $logger;

    /**
     * LpaAlreadyAdded constructor.
     *
     * @param LpaService $lpaService
     * @param LoggerInterface $logger
     * @codeCoverageIgnore
     */
    public function __construct(
        LpaService $lpaService,
        LoggerInterface $logger
    ) {
        $this->lpaService = $lpaService;
        $this->logger = $logger;
    }

    /**
     * @param string $userId
     * @param string $lpaUid
     *
     * @return ArrayObject|null
     */
    public function __invoke(string $userId, string $lpaUid): ?ArrayObject
    {
        // TODO: Would it be better to create a query with a filter rather than this method?
        $lpasAdded = $this->lpaService->getAllForUser($userId);

        foreach ($lpasAdded as $userLpaActorToken => $lpaData) {
            if ($lpaData['lpa']->getUId() === $lpaUid) {
                $this->logger->info(
                    'Account with Id {id} has attempted to add LPA {uId} which already exists in their account',
                    [
                        'id' => $userId,
                        'uId' => $lpaUid
                    ]
                );
                return $lpasAdded[$userLpaActorToken];
            }
        }
        return null;
    }
}
