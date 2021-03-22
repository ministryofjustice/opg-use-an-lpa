<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\ActorCodes\ActorCodeService;
use Psr\Log\LoggerInterface;

class AddLpa
{
    private LoggerInterface $logger;
    private ActorCodeService $actorCodeService;
    private LpaService $lpaService;

    public function __construct(
        LoggerInterface $logger,
        ActorCodeService $actorCodeService,
        LpaService $lpaService
    ) {
        $this->logger = $logger;
        $this->actorCodeService = $actorCodeService;
        $this->lpaService = $lpaService;
    }

    public function validateAddLpaData(array $data): array
    {
    }
}
