<?php

declare(strict_types=1);

namespace App\Service\Lpa\AddLpa;

use App\Exception\BadRequestException;
use App\Exception\LpaAlreadyAddedException;
use App\Exception\LpaNotRegisteredException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use Psr\Log\LoggerInterface;

class AddLpa
{
    public function __construct(
        private LoggerInterface $logger,
        private ActorCodeService $actorCodeService,
        private LpaAlreadyAdded $lpaAlreadyAdded,
    ) {
    }

    /**
     * @param array  $data
     * @param string $userId
     * @return array
     * @throws BadRequestException
     * @throws LpaNotRegisteredException
     * @throws NotFoundException
     */
    public function validateAddLpaData(array $data, string $userId): array
    {
        if (null !== $lpaAddedData = ($this->lpaAlreadyAdded)($userId, $data['uid'])) {
            if (!array_key_exists('notActivated', $lpaAddedData)) {
                $this->logger->notice(
                    'User {id} attempted to add an LPA {uId} which already exists in their account',
                    [
                        'id'  => $userId,
                        'uId' => $data['uid'],
                    ]
                );
                throw new LpaAlreadyAddedException($lpaAddedData);
            }
        }

        $lpaData = $this->actorCodeService->validateDetails($data['actor-code'], $data['uid'], $data['dob']);

        if (!is_array($lpaData)) {
            $this->logger->notice(
                'Code validation failed for user {id}',
                [
                    'id' => $userId,
                ]
            );
            throw new NotFoundException('Code validation failed');
        }

        if (strtolower($lpaData['lpa']->getStatus()) === 'registered') {
            $this->logger->notice(
                'User {id} has found an LPA with Id {uId} using their activation key',
                [
                    'id'  => $userId,
                    'uId' => $data['uid'],
                ]
            );
            return $lpaData;
        }

        $this->logger->notice(
            'Failed to add an LPA for user {id} as the LPA {uId} status is not registered',
            [
                'id'  => $userId,
                'uId' => $data['uid'],
            ]
        );
        throw new LpaNotRegisteredException();
    }
}
