<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ActorCodesInterface;
use Aws\DynamoDb\DynamoDbClient;

class ActorCodes implements ActorCodesInterface
{
    use DynamoHydrateTrait;

    public function __construct(private DynamoDbClient $client, private string $actorLpaCodesTable)
    {
    }

    /**
     * @inheritDoc
     */
    public function get(string $code): ?array
    {
        $result = $this->client->getItem([
            'TableName' => $this->actorLpaCodesTable,
            'Key'       => [
                'ActorCode' => [
                    'S' => $code,
                ],
            ],
        ]);

        $codeData = $this->getData($result, ['Expires']);

        return !empty($codeData) ? $codeData : null;
    }

    /**
     * @inheritDoc
     */
    public function flagCodeAsUsed(string $code)
    {
        $this->client->updateItem([
            'TableName'                 => $this->actorLpaCodesTable,
            'Key'                       => [
                'ActorCode' => [
                    'S' => $code,
                ],
            ],
            'UpdateExpression'          => 'set Active=:active',
            'ExpressionAttributeValues' => [
                ':active' => [
                    'BOOL' => false,
                ],
            ],
        ]);
    }
}
