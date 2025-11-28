<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\Exception\ApiException;
use App\Value\LpaUid;

interface RequestLetterInterface
{
    /**
     * Contacts the api gateway and requests that Sirius send a new actor-code letter to the
     * $actorId that is attached to the LPA $caseId
     *
     * @link https://github.com/ministryofjustice/opg-data-lpa/blob/master/lambda_functions/v1/openapi/lpa-openapi.yml#L334
     *
     * @throws ApiException
     */
    public function requestLetter(LpaUid $caseId, ?string $actorId, ?string $additionalInfo): void;
}
