<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\ActorCodesInterface;
use App\DataAccess\Repository\Response\ActorCodeExists;
use App\DataAccess\Repository\Response\ActorCodeIsValid;
use App\DataAccess\Repository\Response\ResponseInterface;
use App\DataAccess\Repository\Response\UpstreamResponse;
use App\Exception\ApiException;
use DateTimeImmutable;

class ActorCodes extends AbstractApiClient implements ActorCodesInterface
{
    use PostRequest;

    /**
     * @psalm-return ResponseInterface<ActorCodeIsValid>
     * @throws ApiException
     */
    public function validateCode(string $code, string $uid, string $dob): ResponseInterface
    {
        $response = $this->makePostRequest(
            'v1/validate',
            [
                'lpa'  => $uid,
                'dob'  => $dob,
                'code' => $code,
            ],
        );

        $responseData = json_decode((string) $response->getBody(), true);

        return new UpstreamResponse(
            new ActorCodeIsValid($responseData['actor']),
            new DateTimeImmutable($response->getHeaderLine('Date'))
        );
    }

    /**
     * @throws ApiException
     */
    public function flagCodeAsUsed(string $code): void
    {
        $this->makePostRequest('v1/revoke', ['code' => $code]);
    }

    /**
     * @psalm-return ResponseInterface<ActorCodeExists>
     * @throws ApiException
     */
    public function checkActorHasCode(string $lpaId, string $actorId): ResponseInterface
    {
        $response = $this->makePostRequest(
            'v1/exists',
            [
                'lpa'   => $lpaId,
                'actor' => $actorId,
            ],
        );

        $responseData = json_decode((string) $response->getBody(), true);

        $createdAt = isset($responseData['Created'])
            ? new DateTimeImmutable($responseData['Created'])
            : null;

        return new UpstreamResponse(
            new ActorCodeExists($createdAt),
            new DateTimeImmutable($response->getHeaderLine('Date'))
        );
    }
}
