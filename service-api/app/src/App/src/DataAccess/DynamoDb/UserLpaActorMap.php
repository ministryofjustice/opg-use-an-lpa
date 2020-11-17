<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\DataAccess\Repository\KeyCollisionException;
use Aws\DynamoDb\DynamoDbClient;
use DateTime;
use Aws\DynamoDb\Exception\DynamoDbException;

class UserLpaActorMap implements UserLpaActorMapInterface
{
    use DynamoHydrateTrait;

    /**
     * @var DynamoDbClient
     */
    private $client;

    /**
     * @var string
     */
    private $userLpaActorTable;

    /**
     * ViewerCodeActivity constructor.
     * @param DynamoDbClient $client
     * @param string $userLpaActorTable
     */
    public function __construct(DynamoDbClient $client, string $userLpaActorTable)
    {
        $this->client = $client;
        $this->userLpaActorTable = $userLpaActorTable;
    }

    /**
     * @inheritDoc
     */
    public function get(string $lpaActorToken): ?array
    {
        $result = $this->client->getItem([
            'TableName' => $this->userLpaActorTable,
            'Key' => [
                'Id' => [
                    'S' => $lpaActorToken,
                ],
            ],
        ]);

        $codeData = $this->getData($result, ['Added']);

        return !empty($codeData) ? $codeData : null;
    }

    /**
     * @inheritDoc
     */
    public function create(string $lpaActorToken, string $userId, string $siriusUid, int $actorId)
    {
        // The current DateTime, including microseconds
        $now = (new DateTime())->format('Y-m-d\TH:i:s.u\Z');

        try {
            $this->client->putItem([
                'TableName' => $this->userLpaActorTable,
                'Item' => [
                    'Id'        => ['S' => $lpaActorToken],
                    'UserId'    => ['S' => $userId],
                    'SiriusUid' => ['S' => $siriusUid],
                    'ActorId'   => ['N' => (string)$actorId],
                    'Added'     => ['S' => $now],
                ],
                'ConditionExpression' => 'attribute_not_exists(Id)'
            ]);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ConditionalCheckFailedException') {
                throw new KeyCollisionException();
            }
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $lpaActorToken): array
    {
        $response = $this->client->deleteItem([
            'TableName' => $this->userLpaActorTable,
            'Key' => [
                'Id' => [
                    'S' => $lpaActorToken,
                ],
            ],
            'ConditionExpression' => 'Id = :id',
            'ExpressionAttributeValues' => [
                ':id' => [
                    'S' => $lpaActorToken
                ],
            ],
            'ReturnValues' => 'ALL_OLD'
        ]);

        return $this->getData($response);
    }

    /**
     * @inheritDoc
     */
    public function getUsersLpas(string $userId): ?array
    {
        $result = $this->client->query([
            'TableName' => $this->userLpaActorTable,
            'IndexName' => 'UserIndex',
            'KeyConditionExpression' => 'UserId = :user_id',
            'ExpressionAttributeValues' => [
                ':user_id' => [
                    'S' => $userId,
                ],
            ]
        ]);

        return $this->getDataCollection($result, ['Added']);
    }
}
