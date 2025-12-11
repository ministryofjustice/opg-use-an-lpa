<?php

declare(strict_types=1);

namespace App\Service\Lpa\AddLpa;

use App\Exception\ApiException;
use App\Exception\LpaAlreadyAddedException;
use App\Exception\LpaNotRegisteredException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\ActorCodes\ValidatedActorCode;
use App\Value\LpaUid;
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
     * @return ValidatedActorCode
     * @throws LpaAlreadyAddedException
     * @throws LpaNotRegisteredException
     * @throws NotFoundException
     * @throws ApiException
     */
    public function validateAddLpaData(array $data, string $userId): ValidatedActorCode
    {
        if (null !== $lpaAddedData = ($this->lpaAlreadyAdded)($userId, new LpaUid($data['uid']))) {
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

        $validatedDetails = $this->actorCodeService->validateDetails(
            $data['actor-code'],
            new LpaUid($data['uid']),
            $data['dob']
        );

        if (! $validatedDetails instanceof ValidatedActorCode) {
            $this->logger->notice(
                'Code validation failed for user {id}',
                [
                    'id' => $userId,
                ]
            );
            throw new NotFoundException('Code validation failed');
        }

        if (strtolower($validatedDetails->lpa->getStatus()) === 'registered') {
            $this->logger->notice(
                'User {id} has found an LPA with Id {uId} using their activation key',
                [
                    'id'  => $userId,
                    'uId' => $data['uid'],
                ]
            );
            return $validatedDetails;
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
