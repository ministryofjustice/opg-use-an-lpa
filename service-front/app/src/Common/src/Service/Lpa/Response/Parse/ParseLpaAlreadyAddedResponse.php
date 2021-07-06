<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Transformer;

use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use Laminas\Stdlib\Exception\InvalidArgumentException;

class ParseLpaAlreadyAddedResponse
{
    public function __invoke(array $data): LpaAlreadyAddedResponse
    {
        if (
            !isset($data['donorName']) ||
            !isset($data['caseSubtype']) ||
            !isset($data['lpaActorToken'])
        ) {
            throw new InvalidArgumentException(
                'The data array passed to ' . __METHOD__ . ' does not contain the required fields'
            );
        }

        $response = new LpaAlreadyAddedResponse();
        $response->setDonorName($data['donorName']);
        $response->setCaseSubtype($data['caseSubtype']);
        $response->setLpaActorToken($data['lpaActorToken']);
        return $response;
    }
}
