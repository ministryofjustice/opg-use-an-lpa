<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Common\Form\Fieldset\Date;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
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
        ?DateInterval $expiryInterval = null,
        ?DateInterval $dueByInterval = null
    ): string {
        $added = new DateTimeImmutable('now', new DateTimeZone('Etc/UTC'));

        $array = [
            'UserId'    => ['S' => $userId],
            'SiriusUid' => ['S' => $siriusUid],
            'Added'     => ['S' => $added->format(DateTimeInterface::ATOM)]
        ];

        if (isset($actorId)) {
            $array['ActorId'] = ['N' => $actorId];
        }

        // Add ActivateBy field to array if expiry interval is present
        if ($expiryInterval !== null) {
            $expiry = $added->add($expiryInterval);
            $array['ActivateBy'] = ['N' => (string) $expiry->getTimestamp()];

            $dueBy = $added->add($dueByInterval);
            $array['DueBy'] = ['S' => $dueBy->format(DateTimeInterface::ATOM)];
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
    public function activateRecord(string $lpaActorToken, $actorId): array
    {
        $response = $this->client->updateItem([
          'TableName' => $this->userLpaActorTable,
          'Key' => [
              'Id' => [
                  'S' => $lpaActorToken,
              ],
          ],
          'UpdateExpression' => 'set ActorId = :a remove ActivateBy, DueBy',
          'ExpressionAttributeValues' => [
              ':a' => [
                  'N' => $actorId
              ]
          ],
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
    public function updateRecord(
        string $lpaActorToken,
        DateInterval $expiryInterval,
        DateInterval $intervalTillDue,
        ?string $actorId
    ): array {
        $now = new DateTimeImmutable();
        $expiry = $now->add($expiryInterval);
        $dueBy = $now->add($intervalTillDue);

        $updateRequest = [
            'TableName' => $this->userLpaActorTable,
            'Key' => [
                'Id' => [
                    'S' => $lpaActorToken,
                ],
            ],
            'UpdateExpression' => 'set ActivateBy = :a, DueBy = :b',
            'ExpressionAttributeValues' => [
                ':a' => ['N' => (string) $expiry->getTimestamp()],
                ':b' => ['S' => $dueBy->format(DateTimeInterface::ATOM)]
            ],
            'ReturnValues' => 'ALL_NEW',
        ];

        if ($actorId !== null) {
            $updateRequest['UpdateExpression'] = $updateRequest['UpdateExpression'] . ', ActorId = :c';
            $updateRequest['ExpressionAttributeValues'][':c'] = ['N' => $actorId];
        }

        $response = $this->client->updateItem($updateRequest);
        return $this->getData($response);
    }
}
