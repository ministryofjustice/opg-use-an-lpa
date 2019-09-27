<?php

namespace App\DataAccess\DynamoDb;

use Aws\Result;
use DateTime;
use Aws\DynamoDb\Marshaler;

/**
 * Trait DynamoHydrateTrait
 * @package App\DataAccess\DynamoDb
 */
trait DynamoHydrateTrait
{
    /**
     * @param Result $result
     * @param array $dateFields
     * @return array
     */
    private function getData(Result $result,  array $dateFields = [])
    {
        if (isset($result['Item'])) {
            return $this->extractData($result['Item'], $dateFields);
        }

        // updateItem calls return an AWS Result object with "Attributes"
        if (isset($result['Attributes'])) {
            return $this->extractData($result['Attributes'], $dateFields);
        }

        return [];
    }

    /**
     * @param Result $result
     * @param array $dateFields
     * @return array
     */
    private function getDataCollection(Result $result,  array $dateFields = [])
    {
        $items = [];

        if (isset($result['Items'])) {
            foreach ($result['Items'] as $item) {
                $items[] = $this->extractData($item, $dateFields);
            }
        }

        return $items;
    }

    /**
     * @param array $resultItem
     * @param array $dateFields
     * @return array
     */
    private function extractData(array $resultItem, array $dateFields = []) : array
    {
        $item = [];

        $marshaler = new Marshaler();

        foreach ($resultItem as $key => $value) {
            $thisVal = $marshaler->unmarshalValue($value);

            if (in_array($key, $dateFields)) {
                $thisVal = new DateTime($thisVal);
            }

            $item[$key] = $thisVal;
        }

        return $item;
    }
}
