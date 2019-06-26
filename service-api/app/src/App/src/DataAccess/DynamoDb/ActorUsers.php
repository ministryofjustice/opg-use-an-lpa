<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;

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
    public function add(string $email, string $password) : array
    {
        $this->client->putItem([
            'TableName' => $this->actorUsersTable,
            'Item' => [
                'Email' => ['S' => $email],
                'Password' => ['S' => password_hash($password, PASSWORD_DEFAULT)],
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
    public function exists($email) : bool
    {
        try {
            $userData = $this->get($email);
        } catch (NotFoundException $nfe) {
            return false;
        }

        return true;
    }
}
