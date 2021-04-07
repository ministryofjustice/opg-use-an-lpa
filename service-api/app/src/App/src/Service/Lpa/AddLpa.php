<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use Psr\Log\LoggerInterface;

/**
 * Class AddLpa
 * @package App\Service\Lpa
 */
class AddLpa
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

    /**
     * @param array  $data
     * @param string $userId
     *
     * @return array
     */
    public function validateAddLpaData(array $data, string $userId): array
    {
        if (null !== $lpaAddedData = ($this->lpaAlreadyAdded)($userId, $data['uid'])) {
            $this->logger->notice(
                'User {id} attempted to add an LPA {uId} which already exists in their account',
                [
                    'id' => $userId,
                    'uId' => $data['uid']
                ]
            );
            throw new BadRequestException('LPA already added', $lpaAddedData);
        }

        $lpaData = $this->actorCodeService->validateDetails($data['actor-code'], (string) $data['uid'], $data['dob']);

        if (!is_array($lpaData)) {
            $this->logger->notice(
                'Code validation failed for user {id}',
                [
                    'id' => $userId,
                ]
            );
            throw new NotFoundException('Code validation failed');
        }

        if (strtolower($lpaData['lpa']['status']) === 'registered') {
            $this->logger->notice(
                'User {id} has found their LPA with Id {uId} using their activation key',
                [
                    'id' => $userId,
                    'uId' => $data['uid']
                ]
            );
            return $lpaData;
        }

        $this->logger->notice(
            'Failed to add an LPA for user {id} as the LPA {uId} status is not registered',
            [
                'id' => $userId,
                'uId' => $data['uid']
            ]
        );
        throw new BadRequestException('LPA status is not registered');
    }
}
