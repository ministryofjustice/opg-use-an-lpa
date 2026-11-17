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
                        ...$this->identityPinningOperations($identity, $id, $ignoreOrphanIdentity),
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

    /**
     * The identity pin row is read with a strongly consistent read, as a transaction that has just committed
     * an account is always visible through it. The IdentityIndex is only eventually consistent and is
     * used as a fallback for pins that pre-date the recording of their owning account.
     */
    public function getByIdentity(string $identity): array
    {
        $pinOwner = $this->getIdentityPin($identity)['UserId'] ?? null;

        if ($pinOwner !== null) {
            $owner = $this->getConsistentUser($pinOwner);

            if ($owner !== null) {
                return $owner;
            }
        }

        $user = $this->singleIdentityHolder($identity, $this->queryUsersByIdentity($identity));

        // Self heal: record (or correct) the owner of the pin. Losing this race is harmless.
        $this->pinIdentityToUser($identity, $user['Id'], $pinOwner);

        return $user;
    }

    /**
     * @throws NotFoundException
     * @throws ConflictException When more than one account holds the identity, which needs manual resolution
     */
    private function singleIdentityHolder(string $identity, array $users): array
    {
        if ($users === []) {
            throw new NotFoundException('User not found for identity', ['identity' => $identity]);
        }

        if (count($users) > 1) {
            throw new ConflictException(
                'Multiple accounts hold the same identity',
                [
                    'identity' => $identity,
                    'users'    => array_column($users, 'Id'),
                ]
            );
        }

        return $users[0];
    }

    /**
     * @return ?array<string, mixed> null when the pin does not exist
     */
    private function getIdentityPin(string $identity): ?array
    {
        $result = $this->client->getItem([
            'TableName'      => $this->actorUsersTable,
            'Key'            => ['Id' => ['S' => 'IDENTITY#' . $identity]],
            'ConsistentRead' => true,
        ]);

        $pin = $this->getData($result);

        return $pin === [] ? null : $pin;
    }

    private function getConsistentUser(string $id): ?array
    {
        $result = $this->client->getItem([
            'TableName'      => $this->actorUsersTable,
            'Key'            => ['Id' => ['S' => $id]],
            'ConsistentRead' => true,
        ]);

        $user = $this->getData($result);

        return $user === [] ? null : $user;
    }

    private function pinIdentityToUser(string $identity, string $userId, ?string $previousOwner): void
    {
        $values = [':owner' => ['S' => $userId]];

        if ($previousOwner === null) {
            $condition = 'attribute_not_exists(UserId)';
        } else {
            $condition           = 'UserId = :previous';
            $values[':previous'] = ['S' => $previousOwner];
        }

        try {
            $this->client->updateItem([
                'TableName'                 => $this->actorUsersTable,
                'Key'                       => ['Id' => ['S' => 'IDENTITY#' . $identity]],
                'UpdateExpression'          => 'SET UserId = :owner',
                'ConditionExpression'       => $condition,
                'ExpressionAttributeValues' => $values,
            ]);
        } catch (DynamoDbException $ex) {
            if ($ex->getAwsErrorCode() !== 'ConditionalCheckFailedException') {
                throw $ex;
            }
        }
    }

    private function queryUsersByIdentity(string $identity): array
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

        return $this->getDataCollection($result);
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
                            'ConditionExpression'       => 'attribute_exists(Id) AND attribute_not_exists(#sub)',
                            'ExpressionAttributeValues' => $marshaler->marshalItem([':sub' => $identity]),
                            'ExpressionAttributeNames'  => [
                                '#sub' => 'Identity',
                            ],
                        ],
                    ],
                    ...$this->identityPinningOperations($identity, $user['Id'], $ignoreOrphanIdentity),
                ],
            ]);

            $code = $result->get('@metadata')['statusCode'] ?? null;
            if ($code !== StatusCodeInterface::STATUS_OK) {
                throw new CreationException('Failed to migrate account with code', ['code' => $code]);
            }
        } catch (DynamoDbException $ex) {
            // the account was removed, or has been given an identity, since we read it.
            if (($this->cancellationCodes($ex)[0] ?? null) === 'ConditionalCheckFailed') {
                throw new ConflictException(
                    'User account is no longer eligible for migration',
                    ['user' => $user['Id']],
                    $ex,
                );
            }

            $this->throwOnIdentityPinningConflict($ex, $identity);

            throw new CreationException('Failed to migrate account with code', [], $ex);
        }

        $user['Identity'] = $identity;

        return $user;
    }

    /**
     * Builds the transaction items that pin an identity to a user. These must follow the user write.
     *
     * Normally the pin must not exist. When taking over a pin that is known to be an orphan (its account
     * does not exist) the pin is replaced, but only if it is still exactly as we have just seen it. This stops
     * a concurrent request that has legitimately claimed the identity from being overwritten.
     *
     * @return list<array<string, array<string, mixed>>>
     */
    private function identityPinningOperations(string $identity, string $userId, bool $takeOverOrphan): array
    {
        $pin = [
            'Put' => [
                'TableName'           => $this->actorUsersTable,
                'Item'                => [
                    'Id'     => ['S' => 'IDENTITY#' . $identity],
                    'UserId' => ['S' => $userId],
                ],
                'ConditionExpression' => 'attribute_not_exists(Id)',
            ],
        ];

        if (! $takeOverOrphan) {
            return [$pin];
        }

        $existing = $this->getIdentityPin($identity);

        if ($existing === null) {
            return [$pin];
        }

        $previousOwner = $existing['UserId'] ?? null;

        if ($previousOwner === null) {
            // a legacy pin, written before the owner was recorded.
            $pin['Put']['ConditionExpression'] = 'attribute_exists(Id) AND attribute_not_exists(UserId)';

            return [$pin];
        }

        $pin['Put']['ConditionExpression']       = 'UserId = :previous';
        $pin['Put']['ExpressionAttributeValues'] = [':previous' => ['S' => $previousOwner]];

        return [
            $pin,
            [
                'ConditionCheck' => [
                    'TableName'           => $this->actorUsersTable,
                    'Key'                 => ['Id' => ['S' => $previousOwner]],
                    'ConditionExpression' => 'attribute_not_exists(Id)',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function cancellationCodes(DynamoDbException $ex): array
    {
        if ($ex->getAwsErrorCode() !== 'TransactionCanceledException') {
            return [];
        }

        return array_map(
            static fn (array $reason): string => $reason['Code'] ?? '',
            $ex->toArray()['CancellationReasons'] ?? [],
        );
    }

    private function throwOnIdentityPinningConflict(DynamoDbException $ex, string $identity): void
    {
        $codes = $this->cancellationCodes($ex);

        // The user write is always first and unconditional on creation. Any failure of the pin
        // (or of the orphan check that follows it) means the identity is claimed.
        if (($codes[0] ?? null) === 'None' && in_array('ConditionalCheckFailed', array_slice($codes, 1), true)) {
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
