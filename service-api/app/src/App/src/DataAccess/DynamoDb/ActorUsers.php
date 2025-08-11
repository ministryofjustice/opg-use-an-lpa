<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Fig\Http\Message\StatusCodeInterface;
use ParagonIE\HiddenString\HiddenString;

use function password_hash;

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
        $result = $this->client->putItem(
            [
                'TableName' => $this->actorUsersTable,
                'Item'      => [
                    'Id'       => ['S' => $id],
                    'Email'    => ['S' => $email],
                    'Identity' => ['S' => $identity],
                ],
            ]
        );

        $code = $result->get('@metadata')['statusCode'] ?? null;
        if ($code !== StatusCodeInterface::STATUS_OK) {
            throw new CreationException('Failed to create account with code', ['code' => $code]);
        }
    }

    public function get(string $id): array
    {
        $result = $this->client->getItem(
            [
                'TableName' => $this->actorUsersTable,
                'Key'       => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
            ]
        );

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

        return array_pop($usersData);
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

        return array_pop($usersData);
    }

    public function migrateToOAuth(string $id, string $identity): array
    {
        $marshaler = new Marshaler();

        $result = $this->client->updateItem(
            [
                'TableName' => $this->actorUsersTable,
                'Key'       => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
                'UpdateExpression'
                    => 'SET #sub = :sub REMOVE ActivationToken, ExpiresTTL, PasswordResetToken, '
                        . 'PasswordResetExpiry, NeedsReset',
                'ExpressionAttributeValues' => $marshaler->marshalItem(
                    [
                        ':sub' => $identity,
                    ]
                ),
                'ExpressionAttributeNames'  => [
                    '#sub' => 'Identity',
                ],
                'ReturnValues'              => 'ALL_NEW',
            ]
        );

        $user = $this->getData($result);

        if (empty($user)) {
            throw new NotFoundException('User not found when updating', ['id' => $id]);
        }

        return $user;
    }

    public function exists(string $email): bool
    {
        try {
            $this->getByEmail($email);
        } catch (NotFoundException) {
            return false;
        }

        return true;
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

    public function changeEmail(string $id, string $token, string $newEmail): bool
    {
        //  Update the item by setting the new email and removing the reset token/expiry
        $this->client->updateItem(
            [
                'TableName'                 => $this->actorUsersTable,
                'Key'                       => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
                'UpdateExpression'          => 'SET Email=:p REMOVE EmailResetToken, EmailResetExpiry, NewEmail',
                'ExpressionAttributeValues' => [
                    ':p' => [
                        'S' => $newEmail,
                    ],
                ],
            ]
        );

        return true;
    }

    public function delete(string $accountId): array
    {
        $user = $this->client->deleteItem(
            [
                'TableName'                 => $this->actorUsersTable,
                'Key'                       => [
                    'Id' => [
                        'S' => $accountId,
                    ],
                ],
                'ConditionExpression'       => 'Id = :id',
                'ExpressionAttributeValues' => [
                    ':id' => [
                        'S' => $accountId,
                    ],
                ],
                'ReturnValues'              => 'ALL_OLD',
            ]
        );

        return $this->getData($user);
    }

    private function hashPassword(HiddenString $password): string
    {
        return password_hash(
            $password->getString(),
            PASSWORD_DEFAULT,
            [
                'cost' => 13,
            ]
        );
    }
}
