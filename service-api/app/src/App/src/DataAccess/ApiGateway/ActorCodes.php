<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\Response\ActorCode;
use App\Exception\ApiException;
use DateTime;
use Exception;

class ActorCodes extends AbstractApiClient
{
    use PostRequest;

    /**
     * @param string $code
     * @param string $uid
     * @param string $dob
     * @return ActorCode
     * @throws ApiException|Exception
     */
    public function validateCode(string $code, string $uid, string $dob): ActorCode
    {
        $response = $this->makePostRequest(
            'v1/validate',
            [
                'lpa'  => $uid,
                'dob'  => $dob,
                'code' => $code,
            ]
        );

        return new ActorCode(
            json_decode((string) $response->getBody(), true),
            new DateTime($response->getHeaderLine('Date'))
        );
    }

    /**
     * @throws ApiException|Exception
     */
    public function flagCodeAsUsed(string $code): void
    {
        $this->makePostRequest('v1/revoke', ['code' => $code]);
    }

    /**
     * @throws ApiException
     */
    public function checkActorHasCode(string $lpaId, string $actorId): ActorCode
    {
        $response = $this->makePostRequest(
            'v1/exists',
            [
                'lpa'   => $lpaId,
                'actor' => $actorId,
            ],
            SignatureType::ActorCodes,
        );

        return new ActorCode(
            json_decode((string) $response->getBody(), true),
            new DateTime($response->getHeaderLine('Date'))
        );
    }
}
