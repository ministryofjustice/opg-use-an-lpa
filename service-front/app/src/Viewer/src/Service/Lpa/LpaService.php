<?php

namespace Viewer\Service\Lpa;

use Viewer\Service\ApiClient\Initializers\ApiClientInterface;
use Viewer\Service\ApiClient\Initializers\ApiClientTrait;
use ArrayObject;

/**
 * Class LpaService
 * @package Viewer\Service\ApiClient
 */
class LpaService implements ApiClientInterface
{
    use ApiClientTrait;

    /**
     * Get an LPA
     *
     * @param string $shareCode
     * @return ArrayObject|null
     * @throws \Http\Client\Exception
     */
    public function getLpa(string $shareCode) : ?ArrayObject
    {
        $lpaData = $this->getApiClient()->httpGet('/path/to/lpa', [
            'code' => $shareCode,
        ]);

        if (is_array($lpaData)) {
            //  TODO - Transform the data array into a data object
            return new ArrayObject($lpaData);
        }

        return null;
    }
}
