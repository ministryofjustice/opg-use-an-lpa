<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ActorUsersInterface;
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
    public function add(string $email, string $password, string $activationToken, int $activationTtl) : array
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

        return $this->get($email);
    }

    /**
     * @inheritDoc
     */
    public function get(string $email) : array
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
    public function getByToken(string $activationToken) : array
    {
        $data = [
            ':activationToken' => $activationToken,
        ];

        $marshaler = new Marshaler();

        $result = $this->client->query([
            'TableName' => $this->actorUsersTable,
            'IndexName' => 'ActivationTokenIndex',
            'KeyConditionExpression' => 'ActivationToken = :activationToken',
            'ExpressionAttributeValues'=> $marshaler->marshalJson(json_encode($data)),
        ]);

        $userData = $this->getData($result);

var_dump($userData);die();

        if (empty($userData)) {
            throw new NotFoundException('User not found');
        }

        return $userData;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $email) : bool
    {
        try {
            $userData = $this->get($email);
        } catch (NotFoundException $nfe) {
            return false;
        }

        return true;
    }
}
