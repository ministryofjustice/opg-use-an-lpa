<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateInterval;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

class UserLpaActorMap implements UserLpaActorMapInterface
{
    use DynamoHydrateTrait;

    private DynamoDbClient $client;

    private string $userLpaActorTable;

    public function __construct(DynamoDbClient $client, string $userLpaActorTable)
    {
        $this->client = $client;
        $this->userLpaActorTable = $userLpaActorTable;
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws DynamoDbException
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
     * @throws Exception
     * @throws DynamoDbException
     */
    public function create(
        string $userId,
        string $siriusUid,
        ?string $actorId,
        string $expiryInterval = null
    ): string {
        $added = new DateTimeImmutable();
        $array = [
            'UserId'    => ['S' => $userId],
            'SiriusUid' => ['S' => $siriusUid],
            'Added'     => ['S' => $added->format('Y-m-d\TH:i:s.u\Z') ]
        ];

        if ($actorId !== null) {
            $array['ActorId'] = ['N' => $actorId];
        }

        // Add ActivateBy field to array if expiry interval is present
        if ($expiryInterval !== null) {
            $expiry = $added->add(new DateInterval($expiryInterval));
            $array['ActivateBy'] = ['N' => (string) $expiry->getTimestamp()];
        }

        do {
            $id = Uuid::uuid4()->toString();
            $array['Id'] = ['S' => $id];

            try {
                $this->client->putItem([
                    'TableName' => $this->userLpaActorTable,
                    'Item' => $array,
                    'ConditionExpression' => 'attribute_not_exists(Id)'
                ]);

                return $id;
            } catch (DynamoDbException $e) {
                if ($e->getAwsErrorCode() === 'ConditionalCheckFailedException') {
                    continue;
                }
                throw $e;
            }
        } while (true);
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws DynamoDbException
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
     * @throws Exception
     * @throws DynamoDbException
     */
    public function activateRecord(string $lpaActorToken): array
    {
        $response = $this->client->updateItem([
          'TableName' => $this->userLpaActorTable,
          'Key' => [
              'Id' => [
                  'S' => $lpaActorToken,
              ],
          ],
          'UpdateExpression' => 'remove ActivateBy',
          'ReturnValues' => 'ALL_NEW'
          ]);

        return $this->getData($response);
    }


    /**
     * @inheritDoc
     * @throws Exception
     * @throws DynamoDbException
     */
    public function getByUserId(string $userId): ?array
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

    /**
     * @inheritDoc
     * @throws Exception
     * @throws DynamoDbException
     */
    public function renewActivationPeriod(string $lpaActorToken, string $expiryInterval): array
    {
        $now = new DateTimeImmutable();
        $expiry = $now->add(new DateInterval($expiryInterval));

        $response = $this->client->updateItem(
            [
                'TableName' => $this->userLpaActorTable,
                'Key' => [
                    'Id' => [
                        'S' => $lpaActorToken,
                    ],
                ],
                'UpdateExpression' => 'set ActivateBy = :a',
                'ExpressionAttributeValues' => [
                    ':a' => ['N' => (string) $expiry->getTimestamp()]
                ],
                'ReturnValues' => 'ALL_NEW',
            ]
        );

        return $this->getData($response);
    }
}
