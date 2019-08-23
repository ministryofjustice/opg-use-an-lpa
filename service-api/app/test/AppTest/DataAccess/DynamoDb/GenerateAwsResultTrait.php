<?php

namespace AppTest\DataAccess\DynamoDb;

use Aws\Result;
use DateTime;

trait GenerateAwsResultTrait
{
    private function generateAwsResult(array $data) : Result
    {
        return new Result([
            'Item' => $this->parseToAwsItemArray($data)
        ]);
    }

    private function generateAwsResultCollection(array $dataItems) : Result
    {
        //  Reformat the data array and set in an AWS response
        foreach ($dataItems as $idx => $data) {
            $dataItems[$idx] = $this->parseToAwsItemArray($data);
        }

        return new Result([
            'Items' => $dataItems,
        ]);
    }

    private function parseToAwsItemArray(array $data) : array
    {
        foreach ($data as $field => $value) {
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }

            $data[$field] = [
                (is_numeric($value) ? 'N' : 'S') => $value,
            ];
        }

        return $data;
    }
}
