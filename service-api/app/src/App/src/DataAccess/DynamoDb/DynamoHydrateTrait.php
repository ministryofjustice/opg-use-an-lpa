<?php

namespace App\DataAccess\DynamoDb;

use Aws\Result;
use DateTime;

trait DynamoHydrateTrait
{
    /**
     * @param Result $result
     * @param array $dateFields
     * @return array
     */
    private function getData(Result $result, array $dateFields = [])
    {
        $item = [];

        if (isset($result['Item'])) {
            $values = $result['Item'];

            foreach ($values as $key => $value) {
                $thisVal = current($value);

                if (in_array($key, $dateFields)) {
                    $thisVal = DateTime::createFromFormat('Y-m-d H:i:s', $thisVal);
                }

                $item[$key] = $thisVal;
            }
        }

        return $item;
    }
}
