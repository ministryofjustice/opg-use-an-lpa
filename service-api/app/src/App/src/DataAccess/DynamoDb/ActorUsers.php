<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Fig\Http\Message\StatusCodeInterface;

class ActorUsers implements ActorUsersInterface
{
    use DynamoHydrateTrait;

    public function __construct(
        private readonly DynamoDbClient $client,
        private readonly string $actorUsersTable,
    ) {
    }

    public function add(
        string $id,
        string $email,
        string $identity,
    ): void {
        $result = $this->client->transactWriteItems([
            'TransactItems' => [
                [
                    'Put' => [
                        'TableName' => $this->actorUsersTable,
                        'Item'      => [
                            'Id'       => ['S' => $id],
                            'Email'    => ['S' => $email],
                            'Identity' => ['S' => $identity],
                        ],
                    ],
                ],
                [
                    'Put' => [
                        'TableName'           => $this->actorUsersTable,
                        'ConditionExpression' => 'attribute_not_exists(Id)',
                        'Item'                => [
                            'Id' => ['S' => 'IDENTITY#' . $identity],
                        ],
                    ],
                ],
                [
                    'Put' => [
                        'TableName'           => $this->actorUsersTable,
                        'ConditionExpression' => 'attribute_not_exists(Id)',
                        'Item'                => [
                            'Id' => ['S' => 'EMAIL#' . $email],
                        ],
                    ],
                ],
            ],
        ]);

        $code = $result->get('@metadata')['statusCode'] ?? null;
        if ($code !== StatusCodeInterface::STATUS_OK) {
            throw new CreationException('Failed to create account with code', ['code' => $code]);
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

    /**
     * @see https://github.com/vimeo/psalm/issues/10292
     *
     * @psalm-suppress PossiblyUnusedReturnValue
     */
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

    public function migrateToOAuth(array $user, string $identity): array
    {
        // Update the data manually, so we don't have to get it again
        $user['Identity'] = $identity;
        unset($user['ActivationToken']);
        unset($user['ExpiresTTL']);
        unset($user['PasswordResetToken']);
        unset($user['PasswordResetExpiry']);
        unset($user['NeedsReset']);

        $marshaler = new Marshaler();

        $this->client->transactWriteItems([
            'TransactItems' => [
                [
                    'Update' => [
                        'TableName'                 => $this->actorUsersTable,
                        'Key'                       => ['Id' => ['S' => $user['Id']]],
                        'UpdateExpression'          => 'SET #sub = :sub REMOVE ActivationToken, ExpiresTTL, PasswordResetToken, '
                            . 'PasswordResetExpiry, NeedsReset',
                        'ExpressionAttributeValues' => $marshaler->marshalItem([':sub' => $identity]),
                        'ExpressionAttributeNames'  => [
                            '#sub' => 'Identity',
                        ],
                    ],
                ],
                [
                    'Put' => [
                        'TableName'           => $this->actorUsersTable,
                        'ConditionExpression' => 'attribute_not_exists(Id)',
                        'Item'                => [
                            'Id' => ['S' => 'IDENTITY#' . $identity],
                        ],
                    ],
                ],
                // But not EMAIL# as we will be inserting this for all existing
                // accounts manually. That way we can be sure that no email will
                // be used twice.
            ],
        ]);

        return $user;
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
        $this->client->transactWriteItems([
            'TransactItems' => [
                [
                    'Update' => [
                        'TableName'                 => $this->actorUsersTable,
                        'Key'                       => ['Id' => ['S' => $id]],
                        'UpdateExpression'          => 'SET Email=:p REMOVE EmailResetToken, EmailResetExpiry, NewEmail',
                        'ExpressionAttributeValues' => [
                            ':p' => [
                                'S' => $newEmail,
                            ],
                        ],
                    ],
                ],
                [
                    'Delete' => [
                        'TableName' => $this->actorUsersTable,
                        'Key'       => [
                            'Id' => ['S' => 'EMAIL#' . $oldEmail],
                        ],
                    ],
                ],
                [
                    'Put' => [
                        'TableName'           => $this->actorUsersTable,
                        'ConditionExpression' => 'attribute_not_exists(Id)',
                        'Item'                => [
                            'Id' => ['S' => 'EMAIL#' . $newEmail],
                        ],
                    ],
                ],
            ],
        ]);
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
            [
                'Delete' => [
                    'TableName' => $this->actorUsersTable,
                    'Key'       => ['Id' => ['S' => 'EMAIL#' . $user['Email']]],
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
