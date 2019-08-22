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
    public function add(string $email, string $password, string $activationToken, int $activationTtl): array
    {
        $this->client->putItem([
            'TableName' => $this->actorUsersTable,
            'Item' => [
                'Email' => ['S' => $email],
                'Password' => ['S' => password_hash($password, PASSWORD_DEFAULT)],
                'ActivationToken' => ['S' => $activationToken],
                'ExpiresTTL' => ['N' => (string) $activationTtl],
            ]
        ]);

        try {
            return $this->get($email);
        } catch(NotFoundException $nfe) {
            throw new CreationException('Unable to retrieve newly created actor from database', null, $nfe);
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $email): array
    {
        $result = $this->client->getItem([
            'TableName' => $this->actorUsersTable,
            'Key' => [
                'Email' => [
                    'S' => $email,
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
        $email = $userData['Email'];

        //  Update the item by removing the activation token
        $result = $this->client->updateItem([
            'TableName' => $this->actorUsersTable,
            'Key' => [
                'Email' => [
                    'S' => $email,
                ],
            ],
            'UpdateExpression' => 'remove ActivationToken',
        ]);

        return $this->get($email);
    }

    /**
     * @inheritDoc
     */
    public function exists(string $email): bool
    {
        try {
            $userData = $this->get($email);
        } catch (NotFoundException $nfe) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function resetPassword(string $resetToken, string $password): bool
    {
        $marshaler = new Marshaler();

        $result = $this->client->query([
            'TableName' => $this->actorUsersTable,
            'KeyConditionExpression' => 'PasswordResetToken = :rt',
            'ExpressionAttributeValues'=> $marshaler->marshalItem([
                ':rt' => $resetToken,
            ]),
        ]);

        $usersData = $this->getDataCollection($result);

        if (empty($usersData)) {
            return false;
        }

        //  Use the returned value to get the user
        $userData = array_pop($usersData);
        $email = $userData['Email'];

        //  Update the item by removing the activation token
        $this->client->updateItem([
            'TableName' => $this->actorUsersTable,
            'Key' => [
                'Email' => [
                    'S' => $email,
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
    public function recordSuccessfulLogin(string $email, string $loginTime): void
    {
        $this->client->updateItem([
            'TableName' => $this->actorUsersTable,
            'Key' => [
                'Email' => [
                    'S' => $email,
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
        if (!$this->exists($email)) {
            throw new NotFoundException("User not found");
        }

        $user = $this->client->updateItem([
            'TableName' => $this->actorUsersTable,
            'Key' => [
                'Email' => [
                    'S' => $email,
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
