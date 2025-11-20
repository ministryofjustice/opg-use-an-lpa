<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\Response\InstructionsAndPreferencesImages;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\Lpa;
use App\Exception\ApiException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Service\Lpa\ResolveActor\LpaActor;
use App\Value\LpaUid;

interface LpaManagerInterface
{
    /**
     * Get an LPA using the ID value
     *
     * @param LpaUid $uid           Unique ID of LPA to fetch
     * @param ?string $originatorId A unique identifier that allows upstread auditing of requests
     * @return LpaInterface|null    A processed LPA data transfer object
     * @throws ApiException
     */
    public function getByUid(LpaUid $uid, ?string $originatorId = null): ?LpaInterface;

    /**
     * Given a user token and a user id (who should own the token), return the actor and LPA details
     *
     * @param string $token  UserLpaActorToken that map an LPA to a user account
     * @param string $userId The user account ID that must correlate to the $token
     * @return array{
     *      user-lpa-actor-token: string,
     *      date: string,
     *      lpa: Lpa,
     *      activationKeyDueDate: ?string,
     *      actor: LpaActor
     *  }|array|null A structure that contains processed LPA data and metadata
     * @throws ApiException
     */
    public function getByUserLpaActorToken(string $token, string $userId): ?array;

    /**
     * Return all activated LPAs for the given user_id
     *
     * @param string $userId User account ID to fetch LPAs for
     * @return array         An array of LPA data structures containing processed LPA data and metadata
     * @throws ApiException
     */
    public function getAllActiveForUser(string $userId): array;

    /**
     * Return all LPAs (including not activated) for the given user_id
     *
     * @param string $userId User account ID to fetch LPA and Requests for
     * @return array         An array of LPA data structures containing processed LPA data and metadata
     * @throws ApiException
     */
    public function getAllForUser(string $userId): array;

    /**
     * Get an LPA using the share code.
     *
     * @param string  $viewerCode   A code that directly maps to an LPA
     * @param string  $donorSurname The surname of the donor that must correlate to the $viewerCode
     * @param ?string $organisation An organisation name that will be recorded as used against the $viewerCode
     * @return array{
     *     date: string,
     *     expires: string,
     *     organisation: string,
     *     lpa: Lpa,
     *     iap?: InstructionsAndPreferencesImages,
     * } A structure that contains processed LPA data and metadata
     * @throws ApiException
     * @throws NotFoundException
     * @throws GoneException
     */
    public function getByViewerCode(string $viewerCode, string $donorSurname, ?string $organisation = null): array;
}
