<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ViewerCodesInterface;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;

class ViewerCodes implements ViewerCodesInterface
{
    use DynamoHydrateTrait;

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

        $codeData = $this->getData($result, ['Expires']);

        if (empty($codeData)) {
            throw new NotFoundException('Code not found');
        }

        return $codeData;
    }
}
