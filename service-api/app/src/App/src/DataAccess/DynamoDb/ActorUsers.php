<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Fig\Http\Message\StatusCodeInterface;

final class ActorUsers implements ActorUsersInterface
{
    use DynamoHydrateTrait;

    public function __construct(
        private readonly DynamoDbClient $client,
        private readonly string $actorUsersTable,
    ) {
    }

    /**
     * @throws ConflictException
     * @throws CreationException
     */
    public function add(
        string $id,
        string $email,
        string $identity,
        string $now,
        bool $ignoreOrphanIdentity = false,
    ): void {
        try {
            $result = $this->client->transactWriteItems(
                [
                    'TransactItems' => [
                        [
                            'Put' => [
                                'TableName' => $this->actorUsersTable,
                                'Item'      => [
                                    'Id'        => ['S' => $id],
                                    'Email'     => ['S' => $email],
                                    'Identity'  => ['S' => $identity],
                                    'CreatedAt' => ['S' => $now],
                                ],
                            ],
                        ],
                        $this->identityPinningOperation($identity, $ignoreOrphanIdentity),
                    ],
                ]
            );

            $code = $result->get('@metadata')['statusCode'] ?? null;
            if ($code !== StatusCodeInterface::STATUS_OK) {
                throw new CreationException('Failed to create account with code', ['code' => $code]);
            }
        } catch (DynamoDbException $ex) {
            $this->throwOnIdentityPinningConflict($ex, $identity);

            throw new CreationException('Failed to create account with code', [], $ex);
        }
    }

    public function get(string $id): array
    {
        $result = $this->client->getItem([
            'TableName' => $this->actorUsersTable,
            'Key'       => ['Id' => ['S' => $id]],
        ]);

        $userData = $this->getData($result);

        if (empty($userData)) {
            throw new NotFoundException('User not found');
        }

        return $userData;
    }

    public function getByEmail(string $email): array
    {
        $marshaler = new Marshaler();

        $result = $this->client->query(
            [
                'TableName'                 => $this->actorUsersTable,
                'IndexName'                 => 'EmailIndex',
                'KeyConditionExpression'    => 'Email = :email',
                'ExpressionAttributeValues' => $marshaler->marshalItem(
                    [
                        ':email' => $email,
                    ]
                ),
            ]
        );

        $usersData = $this->getDataCollection($result);

        if (empty($usersData)) {
            throw new NotFoundException('User not found for email', ['email' => $email]);
        }

        return array_pop($usersData)
            ?? throw new NotFoundException('User not found for email', ['email' => $email]);
    }

    public function getByIdentity(string $identity): array
    {
        $marshaler = new Marshaler();

        $result = $this->client->query(
            [
                'TableName'                 => $this->actorUsersTable,
                'IndexName'                 => 'IdentityIndex',
                'KeyConditionExpression'    => '#sub = :sub',
                'ExpressionAttributeValues' => $marshaler->marshalItem(
                    [
                        ':sub' => $identity,
                    ]
                ),
                'ExpressionAttributeNames'  => [
                    '#sub' => 'Identity',
                ],
            ]
        );

        $usersData = $this->getDataCollection($result);

        if (empty($usersData)) {
            throw new NotFoundException('User not found for identity', ['identity' => $identity]);
        }

        return array_pop($usersData)
            ?? throw new NotFoundException('User not found for identity', ['identity' => $identity]);
    }

    public function migrateToOAuth(
        array $user,
        string $identity,
        bool $ignoreOrphanIdentity = false,
    ): array {
        $marshaler = new Marshaler();

        try {
            $result = $this->client->transactWriteItems([
                'TransactItems' => [
                    [
                        'Update' => [
                            'TableName'                 => $this->actorUsersTable,
                            'Key'                       => ['Id' => ['S' => $user['Id']]],
                            'UpdateExpression'          => 'SET #sub = :sub',
                            'ExpressionAttributeValues' => $marshaler->marshalItem([':sub' => $identity]),
                            'ExpressionAttributeNames'  => [
                                '#sub' => 'Identity',
                            ],
                        ],
                    ],
                    $this->identityPinningOperation($identity, $ignoreOrphanIdentity),
                ],
            ]);

            $code = $result->get('@metadata')['statusCode'] ?? null;
            if ($code !== StatusCodeInterface::STATUS_OK) {
                throw new CreationException('Failed to migrate account with code', ['code' => $code]);
            }
        } catch (DynamoDbException $ex) {
            $this->throwOnIdentityPinningConflict($ex, $identity);

            throw new CreationException('Failed to migrate account with code', [], $ex);
        }

        $user['Identity'] = $identity;

        return $user;
    }

    /**
     * @return array{Put: array{
     *     TableName: string,
     *     Item: array{Id: array{S: string}},
     *     ConditionExpression?: string,
     * }}
     */
    private function identityPinningOperation(string $identity, bool $ignoreOrphanIdentity): array
    {
        $operation = [
            'Put' => [
                'TableName' => $this->actorUsersTable,
                'Item'      => [
                    'Id' => ['S' => 'IDENTITY#' . $identity],
                ],
            ],
        ];

        if (! $ignoreOrphanIdentity) {
            $operation['Put']['ConditionExpression'] = 'attribute_not_exists(Id)';
        }

        return $operation;
    }

    private function throwOnIdentityPinningConflict(DynamoDbException $ex, string $identity): void
    {
        // The identity pin is the second operation in both creation and migration transactions.
        if (
            $ex->getAwsErrorCode() === 'TransactionCanceledException'
            && ($ex->toArray()['CancellationReasons'][0]['Code'] ?? null) === 'None'
            && ($ex->toArray()['CancellationReasons'][1]['Code'] ?? null) === 'ConditionalCheckFailed'
        ) {
            throw new ConflictException(
                'User identity/subject pinning already exists',
                ['identity' => $identity],
                $ex,
            );
        }
    }

    public function recordSuccessfulLogin(string $id, string $loginTime): void
    {
        $this->client->updateItem(
            [
                'TableName'                 => $this->actorUsersTable,
                'Key'                       => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
                'UpdateExpression'          => 'SET LastLogin=:ll',
                'ExpressionAttributeValues' => [
                    ':ll' => [
                        'S' => $loginTime,
                    ],
                ],
            ]
        );
    }

    public function changeEmail(string $id, string $oldEmail, string $newEmail): void
    {
        $this->client->updateItem(
            [
                'TableName'                 => $this->actorUsersTable,
                'Key'                       => ['Id' => ['S' => $id]],
                'UpdateExpression'          => 'SET Email=:p',
                'ExpressionAttributeValues' => [
                    ':p' => [
                        'S' => $newEmail,
                    ],
                ],
            ],
        );
    }

    public function delete(string $accountId): array
    {
        $user = $this->get($accountId);

        $items = [
            [
                'Delete' => [
                    'TableName' => $this->actorUsersTable,
                    'Key'       => ['Id' => ['S' => $accountId]],
                ],
            ],
        ];

        if (isset($user['Identity'])) {
            array_push($items, [
                'Delete' => [
                    'TableName' => $this->actorUsersTable,
                    'Key'       => ['Id' => ['S' => 'IDENTITY#' . $user['Identity']]],
                ],
            ]);
        }

        $this->client->transactWriteItems(['TransactItems' => $items]);

        return $user;
    }
}
