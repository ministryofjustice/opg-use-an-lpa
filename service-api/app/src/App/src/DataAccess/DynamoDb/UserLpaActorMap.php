<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class UserLpaActorMap implements UserLpaActorMapInterface
{
    use DynamoHydrateTrait;

    private const DATE_FIELDS = ['Added', 'ActivatedOn', 'DueBy', 'Updated'];

    public function __construct(
        private DynamoDbClient $client,
        private string $userLpaActorTable,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws DynamoDbException
     */
    public function get(string $lpaActorToken): ?array
    {
        $result = $this->client->getItem(
            [
                'TableName' => $this->userLpaActorTable,
                'Key'       => [
                    'Id' => [
                        'S' => $lpaActorToken,
                    ],
                ],
            ]
        );

        $codeData = $this->getData($result, self::DATE_FIELDS);

        $codeData = !empty($codeData) ? $codeData : null;
        if ($codeData === null) {
            $this->logger->notice(
                'When marshalling the result for {token} an empty array was returned',
                [
                    'token'  => $lpaActorToken,
                    'result' => json_encode($result->toArray()),
                ],
            );
        }

        return $codeData;
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
        ?DateInterval $intervalTillDue = null,
        ?string $code = null,
    ): string {
        $added = new DateTimeImmutable('now', new DateTimeZone('Etc/UTC'));

        $array = [
            'UserId'    => ['S' => $userId],
            'SiriusUid' => ['S' => $siriusUid],
            'Added'     => ['S' => $added->format(DateTimeInterface::ATOM)],
            'Updated'   => ['S' => $added->format(DateTimeInterface::ATOM)],
        ];

        if ($actorId !== null) {
            $array['ActorId'] = ['N' => $actorId];
        }

        if ($code !== null) {
            $array['ActivationCode'] = ['S' => $code];
        }

        // Add ActivateBy field to array if expiry interval is present
        if ($expiryInterval !== null) {
            $expiry              = $added->add($expiryInterval);
            $array['ActivateBy'] = ['N' => (string) $expiry->getTimestamp()];

            $dueBy          = $added->add($intervalTillDue);
            $array['DueBy'] = ['S' => $dueBy->format(DateTimeInterface::ATOM)];
        }

        do {
            $id          = Uuid::uuid4()->toString();
            $array['Id'] = ['S' => $id];

            try {
                $this->client->putItem(
                    [
                        'TableName'           => $this->userLpaActorTable,
                        'Item'                => $array,
                        'ConditionExpression' => 'attribute_not_exists(Id)',
                    ]
                );

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
        $response = $this->client->deleteItem(
            [
                'TableName'                 => $this->userLpaActorTable,
                'Key'                       => [
                    'Id' => [
                        'S' => $lpaActorToken,
                    ],
                ],
                'ConditionExpression'       => 'Id = :id',
                'ExpressionAttributeValues' => [
                    ':id' => [
                        'S' => $lpaActorToken,
                    ],
                ],
                'ReturnValues'              => 'ALL_OLD',
            ]
        );

        return $this->getData($response, self::DATE_FIELDS);
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws DynamoDbException
     */
    public function activateRecord(string $lpaActorToken, string $actorId, string $activationCode): array
    {
        $current       = new DateTimeImmutable('now', new DateTimeZone('Etc/UTC'));
        $activatedTime = $current->format(DateTimeInterface::ATOM);

        $response = $this->client->updateItem(
            [
                'TableName' => $this->userLpaActorTable,
                'Key'       => [
                    'Id' => [
                        'S' => $lpaActorToken,
                    ],
                ],
                'UpdateExpression'
                    => 'set ActorId = :a, ActivationCode = :b, ActivatedOn = :c, Updated = :d remove ActivateBy, DueBy',
                'ExpressionAttributeValues' => [
                    ':a' => [
                        'N' => $actorId,
                    ],
                    ':b' => [
                        'S' => $activationCode,
                    ],
                    ':c' => [
                        'S' => $activatedTime,
                    ],
                    ':d' => [
                        'S' => $activatedTime,
                    ],
                ],
                'ReturnValues'              => 'ALL_NEW',
            ]
        );

        return $this->getData($response, self::DATE_FIELDS);
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws DynamoDbException
     */
    public function getByUserId(string $userId): ?array
    {
        $result = $this->client->query(
            [
                'TableName'                 => $this->userLpaActorTable,
                'IndexName'                 => 'UserIndex',
                'KeyConditionExpression'    => 'UserId = :user_id',
                'ExpressionAttributeValues' => [
                    ':user_id' => [
                        'S' => $userId,
                    ],
                ],
            ]
        );

        return $this->getDataCollection($result, self::DATE_FIELDS);
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
        ?string $actorId,
    ): array {
        $now    = new DateTimeImmutable('now', new DateTimeZone('Etc/UTC'));
        $expiry = $now->add($expiryInterval);
        $dueBy  = $now->add($intervalTillDue);

        $updateRequest = [
            'TableName'                 => $this->userLpaActorTable,
            'Key'                       => [
                'Id' => [
                    'S' => $lpaActorToken,
                ],
            ],
            'UpdateExpression'          => 'set ActivateBy = :a, DueBy = :b, Updated = :c',
            'ExpressionAttributeValues' => [
                ':a' => ['N' => (string) $expiry->getTimestamp()],
                ':b' => ['S' => $dueBy->format(DateTimeInterface::ATOM)],
                ':c' => ['S' => $now->format(DateTimeInterface::ATOM)],
            ],
            'ReturnValues'              => 'ALL_NEW',
        ];

        if ($actorId !== null) {
            $updateRequest['UpdateExpression']                = $updateRequest['UpdateExpression'] . ', ActorId = :d';
            $updateRequest['ExpressionAttributeValues'][':d'] = ['N' => $actorId];
        }

        $response = $this->client->updateItem($updateRequest);
        return $this->getData($response, self::DATE_FIELDS);
    }
}
