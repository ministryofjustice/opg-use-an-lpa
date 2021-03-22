<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Exception\BadRequestException;
use App\Service\ActorCodes\ActorCodeService;
use Psr\Log\LoggerInterface;

class AddLpa implements User
{
    private LoggerInterface $logger;
    private ActorCodeService $actorCodeService;
    private LpaService $lpaService;
    private LpaAlreadyAdded $lpaAlreadyAdded;

    public function __construct(
        LoggerInterface $logger,
        ActorCodeService $actorCodeService,
        LpaService $lpaService,
        LpaAlreadyAdded $lpaAlreadyAdded
    ) {
        $this->logger = $logger;
        $this->actorCodeService = $actorCodeService;
        $this->lpaService = $lpaService;
        $this->lpaAlreadyAdded = $lpaAlreadyAdded;
    }

    public function validateAddLpaData(array $data, string $userId): array
    {
        // is LPA already added?
        if (null !== $lpaAddedData = ($this->lpaAlreadyAdded)($userId, $data['uid'])) {
            throw new BadRequestException('LPA already added', $lpaAddedData->getArrayCopy());
        }

        // try get by passcode
            // catch exception & throw not found
            // set rate limit identifier

        // check LPA is 'status: Registered'
            // if not, throw bad request exception

        // figure out whether actor is donor or attorney
            // return required data
    }
}
