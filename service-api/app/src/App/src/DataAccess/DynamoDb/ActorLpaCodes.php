<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ActorLpaCodesInterface;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;

class ActorLpaCodes implements ActorLpaCodesInterface
{
    use DynamoHydrateTrait;

    /**
     * @var DynamoDbClient
     */
    private $client;

    /**
     * @var string
     */
    private $actorLpaCodesTable;

    /**
     * ViewerCodeActivity constructor.
     * @param DynamoDbClient $client
     * @param string $actorLpaCodesTable
     */
    public function __construct(DynamoDbClient $client, string $actorLpaCodesTable)
    {
        $this->client = $client;
        $this->actorLpaCodesTable = $actorLpaCodesTable;
    }

    /**
     * @inheritDoc
     */
    public function get(string $code) : array
    {
        $result = $this->client->getItem([
            'TableName' => $this->actorLpaCodesTable,
            'Key' => [
                'ActorLpaCode' => [
                    'S' => $code,
                ],
            ],
        ]);

        $codeData = $this->getData($result);

        if (empty($codeData)) {
            throw new NotFoundException('Code not found');
        }

        return $codeData;
    }
}
