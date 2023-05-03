<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\ValueObjects\DateTimeImmutable;
use Aws\DynamoDb\Marshaler;
use Aws\Result;
use Exception;
use UnexpectedValueException;

trait DynamoHydrateTrait
{
    /**
     * @param Result $result
     * @param array  $dateFields
     * @return array
     * @throws Exception|UnexpectedValueException
     */
    private function getData(Result $result, array $dateFields = []): array
    {
        if ($result->hasKey('Item')) {
            return $this->extractData($result->get('Item'), $dateFields);
        }

        // updateItem calls return an AWS Result object with "Attributes"
        if ($result->hasKey('Attributes')) {
            return $this->extractData($result->get('Attributes'), $dateFields);
        }

        return [];
    }

    /**
     * @param Result $result
     * @param array  $dateFields
     * @return array
     * @throws Exception|UnexpectedValueException
     */
    private function getDataCollection(Result $result, array $dateFields = []): array
    {
        $items = [];

        if ($result->hasKey('Items')) {
            foreach ($result->get('Items') as $item) {
                $items[] = $this->extractData($item, $dateFields);
            }
        }

        return $items;
    }

    /**
     * @param array $resultItem
     * @param array $dateFields
     * @return array
     * @throws Exception|UnexpectedValueException
     */
    private function extractData(array $resultItem, array $dateFields = []): array
    {
        $item = [];

        $marshaler = new Marshaler();

        foreach ($resultItem as $key => $value) {
            $thisVal = $marshaler->unmarshalValue($value);

            if (in_array($key, $dateFields)) {
                $thisVal = new DateTimeImmutable($thisVal);
            }

            $item[$key] = $thisVal;
        }

        return $item;
    }
}
