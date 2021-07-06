<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Transformer;

use Common\Service\Lpa\Response\ActivationKeyExistsResponse;
use InvalidArgumentException;

class ParseActivationKeyExistsResponse
{
    public function __invoke(array $data): ActivationKeyExistsResponse
    {
        if (!isset($data['donorName']) || !isset($data['caseSubtype'])) {
            throw new InvalidArgumentException(
                'The data array passed to ' . __METHOD__ . ' does not contain the required fields'
            );
        }

        $response = new ActivationKeyExistsResponse();
        $response->setDonorName($data['donorName']);
        $response->setCaseSubtype($data['caseSubtype']);
        return $response;
    }
}
