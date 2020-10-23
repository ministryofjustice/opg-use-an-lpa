<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client as ApiClient;
use DateTime;

/**
 * Class ViewerCodeService
 * @package Common\Service\Lpa
 */
class ViewerCodeService
{
    const SORT_ADDED = 'Added';

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

    /**
     * Cancels a viewer/share code for the given lpa
     *
     * @param string $userToken
     * @param string $lpaId
     * @param string $shareCode
     * @return void
     * @throws ApiException
     */
    public function cancelShareCode(string $userToken, string $lpaId, string $shareCode): void
    {
        $this->apiClient->setUserTokenHeader($userToken);

        $this->apiClient->httpPut('/v1/lpas/' . $lpaId . '/codes', [
             'code' => $shareCode
        ]);
    }

    /**
     * Gets a list of viewer codes for a given lpa
     *
     * @param string $userToken
     * @param string $lpaId
     * @param bool $withActiveCount
     * @return ArrayObject|null
     */
    public function getShareCodes(string $userToken, string $lpaId, bool $withActiveCount): ?ArrayObject
    {
        $this->apiClient->setUserTokenHeader($userToken);

        $shareCodes = $this->apiClient->httpGet('/v1/lpas/' . $lpaId . '/codes');

        //sort the result array to appear in order of most recent added
        usort($shareCodes, function ($a, $b) use (&$sort_by_field_name) {
            return strtotime($b[self::SORT_ADDED]) - strtotime($a[self::SORT_ADDED]);
        });

        if (is_array($shareCodes)) {
            $shareCodes = new ArrayObject($shareCodes, ArrayObject::ARRAY_AS_PROPS);
        }

        if ($withActiveCount) {
            $shareCodes = $this->getNumberOfActiveCodes($shareCodes);
        }

        return $shareCodes;
    }

    /**
     * @param ArrayObject $shareCodes
     * @return ArrayObject|null
     * @throws \Exception
     */
    private function getNumberOfActiveCodes(ArrayObject $shareCodes): ?ArrayObject
    {
        $counter = 0;

        if (!empty($shareCodes[0])) {
            foreach ($shareCodes as $codeKey => $code) {
                //if the code has not expired

                if (new DateTime($code['Expires']) >= (new DateTime('now'))->setTime(23,59,59) &&  !(array_key_exists("Cancelled",$code))) {

                    $counter += 1;
                }
            }
        }

        $shareCodes['activeCodeCount'] = $counter;

        return $shareCodes;
    }
}
