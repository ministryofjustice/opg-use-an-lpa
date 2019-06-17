<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ViewerCodesInterface;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use DateTime;

class ViewerCodes implements ViewerCodesInterface
{
    /**
     * @var DynamoDbClient
     */
    private $client;

    /**
     * @var string
     */
    private $viewerCodesTable;

    /**
     * ViewerCodeActivity constructor.
     * @param DynamoDbClient $client
     * @param string $viewerCodesTable
     */
    public function __construct(DynamoDbClient $client, string $viewerCodesTable)
    {
        $this->client = $client;
        $this->viewerCodesTable = $viewerCodesTable;
    }

    /**
     * @inheritDoc
     */
    public function get(string $code) : array
    {
        $result = $this->client->getItem([
            'TableName' => $this->viewerCodesTable,
            'Key' => [
                'ViewerCode' => [
                    'S' => $code,
                ],
            ],
        ]);

        if (isset($result['Item'])) {
            $values = $result['Item'];

            $item = [];
            $dateFields = ['Expires'];

            foreach ($values as $key => $value) {
                $thisVal = current($value);

                if (in_array($key, $dateFields)) {
                    $thisVal = DateTime::createFromFormat('Y-m-d H:i:s', $thisVal);
                }

                $item[$key] = $thisVal;
            }

            return $item;
        }

        throw new NotFoundException('Code not found');
    }
}
