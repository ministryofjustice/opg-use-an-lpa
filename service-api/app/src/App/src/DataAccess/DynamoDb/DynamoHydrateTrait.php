<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use Aws\DynamoDb\Marshaler;
use Aws\Result;
use DateTime;
use UnexpectedValueException;

trait DynamoHydrateTrait
{
    /**
     * @throws UnexpectedValueException
     */
    private function getData(Result $result, array $dateFields = []): array
    {
        if (($item = $result->hasKey('Item') ? $result->get('Item') : null) !== null) {
            return $this->extractData($item, $dateFields);
        }

        // updateItem calls return an AWS Result object with "Attributes"
        if (($attributes = $result->hasKey('Attributes') ? $result->get('Attributes') : null) !== null) {
            return $this->extractData($attributes, $dateFields);
        }

        return [];
    }

    /**
     * @throws UnexpectedValueException
     */
    private function getDataCollection(Result $result, array $dateFields = []): array
    {
        $items = [];

        if (($dynamoDBItems = $result->hasKey('Items') ? $result->get('Items') : null) !== null) {
            foreach ($dynamoDBItems as $item) {
                $items[] = $this->extractData($item, $dateFields);
            }
        }

        return $items;
    }

    /**
     * @throws UnexpectedValueException
     */
    private function extractData(array $resultItem, array $dateFields = []): array
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
