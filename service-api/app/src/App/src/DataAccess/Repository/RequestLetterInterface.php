<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\Repository\Response\LpaInterface;
use App\Exception\ApiException;
use Psr\Http\Message\ResponseInterface;

interface RequestLetterInterface
{
    /**
     * Contacts the api gateway and requests that Sirius send a new actor-code letter to the
     * $actorId that is attached to the LPA $caseId
     *
     * @link https://github.com/ministryofjustice/opg-data-lpa/blob/master/lambda_functions/v1/openapi/lpa-openapi.yml#L334
     *
     * @param int         $caseId  The Sirius uId of an LPA
     * @param int|null    $actorId The uId of an actor as found attached to an LPA
     * @param string|null $additionalInfo
     * @return void
     * @throws ApiException
     */
    public function requestLetter(int $caseId, ?int $actorId, ?string $additionalInfo): void;
}
