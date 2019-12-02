<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

class ActorUsers implements ActorUsersInterface
{
    use DynamoHydrateTrait;

    /**
     * @var DynamoDbClient
     */
    private $client;

    /**
     * @var string
     */
    private $actorUsersTable;

    /**
     * ViewerCodeActivity constructor.
     * @param DynamoDbClient $client
     * @param string $actorUsersTable
     */
    public function __construct(DynamoDbClient $client, string $actorUsersTable)
    {
        $this->client = $client;
        $this->actorUsersTable = $actorUsersTable;
    }

    /**
     * @inheritDoc
     */
    public function add(string $id, string $email, string $password, string $activationToken, int $activationTtl): array
    {
        $this->client->putItem([
            'TableName' => $this->actorUsersTable,
            'Item' => [
                'Id' => ['S' => $id],
                'Email' => ['S' => $email],
                'Password' => ['S' => password_hash($password, PASSWORD_DEFAULT)],
                'ActivationToken' => ['S' => $activationToken],
                'ExpiresTTL' => ['N' => (string) $activationTtl],
            ]
        ]);

        try {
            return $this->get($id);
        } catch(NotFoundException $nfe) {
            throw new CreationException('Unable to retrieve newly created actor from database', [], $nfe);
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $id): array
    {
        $result = $this->client->getItem([
            'TableName' => $this->actorUsersTable,
            'Key' => [
                'Id' => [
                    'S' => $id,
                ],
            ],
        ]);

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

        $result = $this->client->query([
            'TableName' => $this->actorUsersTable,
            'IndexName' => 'EmailIndex',
            'KeyConditionExpression' => 'Email = :email',
            'ExpressionAttributeValues'=> $marshaler->marshalItem([
                ':email' => $email,
            ]),
        ]);

        $usersData = $this->getDataCollection($result);

        if (empty($usersData)) {
            throw new NotFoundException('User not found');
        }

        return array_pop($usersData);
    }

    public function getIdByPasswordResetToken(string $resetToken): string
    {
        $marshaler = new Marshaler();

        $result = $this->client->query([
            'TableName' => $this->actorUsersTable,
            'IndexName' => 'PasswordResetTokenIndex',
            'KeyConditionExpression' => 'PasswordResetToken = :rt',
            'ExpressionAttributeValues'=> $marshaler->marshalItem([
                ':rt' => $resetToken,
            ]),
        ]);

        $usersData = $this->getDataCollection($result);

        if (empty($usersData)) {
            throw new NotFoundException('User not found');
        }

        return (array_pop($usersData))['Id'];
    }

    /**
     * @inheritDoc
     */
    public function activate(string $activationToken): array
    {
        $marshaler = new Marshaler();

        $result = $this->client->query([
            'TableName' => $this->actorUsersTable,
            'IndexName' => 'ActivationTokenIndex',
            'KeyConditionExpression' => 'ActivationToken = :activationToken',
            'ExpressionAttributeValues'=> $marshaler->marshalItem([
                ':activationToken' => $activationToken,
            ]),
        ]);

        $usersData = $this->getDataCollection($result);

        if (empty($usersData)) {
            throw new NotFoundException('User not found for token');
        }

        //  Use the returned value to get the user
        $userData = array_pop($usersData);
        $id = $userData['Id'];

        //  Update the item by removing the activation token
        $this->client->updateItem([
            'TableName' => $this->actorUsersTable,
            'Key' => [
                'Id' => [
                    'S' => $id,
                ],
            ],
            'UpdateExpression' => 'remove ActivationToken, ExpiresTTL',
        ]);

        return $this->get($id);
    }

    /**
     * @inheritDoc
     */
    public function exists(string $email): bool
    {
        try {
            $_ = $this->getByEmail($email);
        } catch (NotFoundException $nfe) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function resetPassword(string $id, string $password): bool
    {
        //  Update the item by setting the password and removing the reset token/expiry
        $this->client->updateItem([
            'TableName' => $this->actorUsersTable,
            'Key' => [
                'Id' => [
                    'S' => $id,
                ],
            ],
            'UpdateExpression' => 'SET Password=:p REMOVE PasswordResetToken, PasswordResetExpiry',
            'ExpressionAttributeValues'=> [
                ':p' => [
                    'S' => password_hash($password, PASSWORD_DEFAULT)
                ]
            ]
        ]);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function recordSuccessfulLogin(string $id, string $loginTime): void
    {
        $this->client->updateItem([
            'TableName' => $this->actorUsersTable,
            'Key' => [
                'Id' => [
                    'S' => $id,
                ],
            ],
            'UpdateExpression' =>
                'SET LastLogin=:ll',
            'ExpressionAttributeValues'=> [
                ':ll' => [
                    'S' => $loginTime
                ]
            ]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function recordPasswordResetRequest(string $email, string $resetToken, int $resetExpiry): array
    {
        $userData = $this->getByEmail($email);
        $id = $userData['Id'];

        $user = $this->client->updateItem([
            'TableName' => $this->actorUsersTable,
            'Key' => [
                'Id' => [
                    'S' => $id,
                ],
            ],
            'UpdateExpression' =>
                'SET PasswordResetToken=:rt, PasswordResetExpiry=:re',
            'ExpressionAttributeValues'=> [
                ':rt' => [
                    'S' => $resetToken
                ],
                ':re' => [
                    'N' => (string) $resetExpiry
                ]
            ],
            'ReturnValues' => 'ALL_NEW'
        ]);

        return $this->getData($user);
    }
}
