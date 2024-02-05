<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use ParagonIE\HiddenString\HiddenString;

use function password_hash;

/**
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
class ActorUsers implements ActorUsersInterface
{
    use DynamoHydrateTrait;

    public function __construct(
        private readonly DynamoDbClient $client,
        private readonly string $actorUsersTable,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function add(
        string $id,
        string $email,
        HiddenString $password,
        string $activationToken,
        int $activationTtl,
    ): void {
        $result = $this->client->putItem(
            [
                'TableName' => $this->actorUsersTable,
                'Item'      => [
                    'Id'              => ['S' => $id],
                    'Email'           => ['S' => $email],
                    'Password'        => ['S' => $this->hashPassword($password)],
                    'ActivationToken' => ['S' => $activationToken],
                    'ExpiresTTL'      => ['N' => (string)$activationTtl],
                ],
            ]
        );

        $code = $result->get('@metadata')['statusCode'];
        if ($code !== 200) {
            throw new CreationException('Failed to create account with code', ['code' => $code]);
        }
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
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

        return array_pop($usersData);
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getUserByNewEmail(string $newEmail): array
    {
        $marshaler = new Marshaler();

        $result = $this->client->query(
            [
                'TableName'                 => $this->actorUsersTable,
                'IndexName'                 => 'NewEmailIndex',
                'KeyConditionExpression'    => 'NewEmail = :newEmail',
                'ExpressionAttributeValues' => $marshaler->marshalItem(
                    [
                        ':newEmail' => $newEmail,
                    ]
                ),
            ]
        );

        return $this->getDataCollection($result);
    }

    /**
     * @inheritDoc
     */
    public function getIdByPasswordResetToken(string $resetToken): string
    {
        $marshaler = new Marshaler();

        $result = $this->client->query(
            [
                'TableName'                 => $this->actorUsersTable,
                'IndexName'                 => 'PasswordResetTokenIndex',
                'KeyConditionExpression'    => 'PasswordResetToken = :rt',
                'ExpressionAttributeValues' => $marshaler->marshalItem(
                    [
                        ':rt' => $resetToken,
                    ]
                ),
            ]
        );

        $usersData = $this->getDataCollection($result);

        if (empty($usersData)) {
            throw new NotFoundException('User not found for password reset token');
        }

        return array_pop($usersData)['Id'];
    }

    /**
     * @inheritDoc
     */
    public function getIdByEmailResetToken(string $resetToken): string
    {
        $marshaler = new Marshaler();

        $result = $this->client->query(
            [
                'TableName'                 => $this->actorUsersTable,
                'IndexName'                 => 'EmailResetTokenIndex',
                'KeyConditionExpression'    => 'EmailResetToken = :rt',
                'ExpressionAttributeValues' => $marshaler->marshalItem(
                    [
                        ':rt' => $resetToken,
                    ]
                ),
            ]
        );

        $usersData = $this->getDataCollection($result);

        if (empty($usersData)) {
            throw new NotFoundException('User not found for email reset token');
        }

        return array_pop($usersData)['Id'];
    }

    /**
     * @inheritDoc
     */
    public function activate(string $activationToken): array
    {
        $marshaler = new Marshaler();

        $result = $this->client->query(
            [
                'TableName'                 => $this->actorUsersTable,
                'IndexName'                 => 'ActivationTokenIndex',
                'KeyConditionExpression'    => 'ActivationToken = :activationToken',
                'ExpressionAttributeValues' => $marshaler->marshalItem(
                    [
                        ':activationToken' => $activationToken,
                    ]
                ),
            ]
        );

        $usersData = $this->getDataCollection($result);

        if (empty($usersData)) {
            throw new NotFoundException('User not found for token');
        }

        //  Use the returned value to get the user
        $userData = array_pop($usersData);
        $id       = $userData['Id'];

        //  Update the item by removing the activation token
        $this->client->updateItem(
            [
                'TableName'        => $this->actorUsersTable,
                'Key'              => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
                'UpdateExpression' => 'remove ActivationToken, ExpiresTTL',
            ]
        );

        return $this->get($id);
    }

    /**
     * @inheritDoc
     */
    public function exists(string $email): bool
    {
        try {
            $this->getByEmail($email);
        } catch (NotFoundException) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function resetPassword(string $id, HiddenString $password): bool
    {
        //  Update the item by setting the password and removing the reset token/expiry
        $this->client->updateItem(
            [
                'TableName' => $this->actorUsersTable,
                'Key'       => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
                'UpdateExpression'
                    => 'SET Password=:p REMOVE PasswordResetToken, PasswordResetExpiry, NeedsReset',
                'ExpressionAttributeValues' => [
                    ':p' => [
                        'S' => $this->hashPassword($password),
                    ],
                ],
            ]
        );

        return true;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function recordPasswordResetRequest(string $email, string $resetToken, int $resetExpiry): array
    {
        $userData = $this->getByEmail($email);
        $id       = $userData['Id'];

        $user = $this->client->updateItem(
            [
                'TableName'                 => $this->actorUsersTable,
                'Key'                       => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
                'UpdateExpression'          => 'SET PasswordResetToken=:rt, PasswordResetExpiry=:re',
                'ExpressionAttributeValues' => [
                    ':rt' => [
                        'S' => $resetToken,
                    ],
                    ':re' => [
                        'N' => (string)$resetExpiry,
                    ],
                ],
                'ReturnValues'              => 'ALL_NEW',
            ]
        );

        return $this->getData($user);
    }

    /**
     * @inheritDoc
     */
    public function recordChangeEmailRequest(string $id, string $newEmail, string $resetToken, int $resetExpiry): array
    {
        $user = $this->client->updateItem(
            [
                'TableName'                 => $this->actorUsersTable,
                'Key'                       => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
                'UpdateExpression'          => 'SET EmailResetToken=:rt, EmailResetExpiry=:re, NewEmail=:ne',
                'ExpressionAttributeValues' => [
                    ':rt' => [
                        'S' => $resetToken,
                    ],
                    ':re' => [
                        'N' => (string)$resetExpiry,
                    ],
                    ':ne' => [
                        'S' => $newEmail,
                    ],
                ],
                'ReturnValues'              => 'ALL_NEW',
            ]
        );

        return $this->getData($user);
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function resetActivationDetails(string $id, HiddenString $password, int $activationTtl): array
    {
        //  Update the item by setting the password and restarting the Expiry TTL
        $result = $this->client->updateItem(
            [
                'TableName'                 => $this->actorUsersTable,
                'Key'                       => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
                'UpdateExpression'          => 'SET Password=:p, ExpiresTTL=:et',
                'ExpressionAttributeValues' => [
                    ':p'  => [
                        'S' => $this->hashPassword($password),
                    ],
                    ':et' => [
                        'N' => (string)$activationTtl,
                    ],
                ],
                'ReturnValues'              => 'ALL_NEW',
            ]
        );
        return $this->getData($result);
    }

    /**
     * @inheritDoc
     */
    public function rehashPassword(string $id, HiddenString $password): bool
    {
        //  Update the item by setting the password
        $this->client->updateItem(
            [
                'TableName'                 => $this->actorUsersTable,
                'Key'                       => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
                'UpdateExpression'          => 'SET Password=:p',
                'ExpressionAttributeValues' => [
                    ':p' => [
                        'S' => $this->hashPassword($password),
                    ],
                ],
            ]
        );

        return true;
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
