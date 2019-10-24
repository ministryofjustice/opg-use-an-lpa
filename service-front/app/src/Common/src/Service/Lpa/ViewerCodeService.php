<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Service\ApiClient\Client as ApiClient;
use Zend\Stdlib\ArrayObject;

/**
 * Class ViewerCodeService
 * @package Common\Service\Lpa
 */
class ViewerCodeService {

    /**
     * @var ApiClient
     */
    private $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Creates a viewer/share code for the given lpa
     *
     * @param string $userToken
     * @param string $lpaId
     * @param string $organisation
     * @return ArrayObject|null
     */
    public function createShareCode(string $userToken, string $lpaId, string $organisation): ?ArrayObject
    {
        $this->apiClient->setUserTokenHeader($userToken);

        $lpaData = $this->apiClient->httpPost('/v1/lpas/' . $lpaId . '/codes', [
            'organisation' => $organisation
        ]);

        if (is_array($lpaData)) {
            $lpaData = new ArrayObject($lpaData, ArrayObject::ARRAY_AS_PROPS);
        }

        return $lpaData;
    }
}