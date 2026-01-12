<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorUsers;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ActorUsersTest extends TestCase
{
    use GenerateAwsResultTrait;
    use ProphecyTrait;

    public const string TABLE_NAME = 'test-table-name';

    private ObjectProphecy $dynamoDbClientProphecy;

    protected function setUp(): void
    {
        $this->dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
    }

    #[Test]
    public function will_add_a_new_user(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $email    = 'a@b.com';
        $identity = '67890-6789-6789-6789-67890';
        $now      = '2020-12-12T12:12:12';

        $items = [
            [
                'Put' => [
                    'TableName' => self::TABLE_NAME,
                    'Item'      => [
                        'Id'        => ['S' => $id],
                        'Email'     => ['S' => $email],
                        'Identity'  => ['S' => $identity],
                        'CreatedAt' => ['S' => $now],
                    ],
                ],
            ],
            [
                'Put' => [
                    'TableName'           => self::TABLE_NAME,
                    'ConditionExpression' => 'attribute_not_exists(Id)',
                    'Item'                => [
                        'Id' => ['S' => 'IDENTITY#' . $identity],
                    ],
                ],
            ],
            [
                'Put' => [
                    'TableName'           => self::TABLE_NAME,
                    'ConditionExpression' => 'attribute_not_exists(Id)',
                    'Item'                => [
                        'Id' => ['S' => 'EMAIL#' . $email],
                    ],
                ],
            ],
        ];

        $this->dynamoDbClientProphecy
            ->transactWriteItems(['TransactItems' => $items])
            ->shouldBeCalled()
            ->willReturn($this->createAWSResult(['@metadata' => ['statusCode' => 200]]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $actorRepo->add($id, $email, $identity, $now);
    }

    #[Test]
    public function will_throw_exception_when_adding_a_new_user_that_doesnt_succeed(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $email    = 'a@b.com';
        $identity = 'urn:fdc:one-login:2023:HASH=';

        $this->dynamoDbClientProphecy->transactWriteItems(Argument::any())
            ->willReturn($this->createAWSResult(['@metadata' => ['statusCode' => 500]]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(CreationException::class);
        $this->expectExceptionMessage('Failed to create account with code');

        $actorRepo->add($id, $email, $identity, 'now');
    }

    #[Test]
    public function will_get_a_user_record(): void
    {
        $id = '12345-1234-1234-1234-12345';

        $this->dynamoDbClientProphecy->getItem(
            Argument::that(function (array $data) use ($id) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('Id', $data['Key']);

                $this->assertEquals(['S' => $id], $data['Key']['Id']);

                return true;
            })
        )->willReturn(
            $this->createAWSResult([
                'Item' => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
            ])
        );

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $actorRepo->get($id);

        $this->assertEquals($id, $result['Id']);
    }

    #[Test]
    public function will_get_a_user_record_by_identity(): void
    {
        $identity = 'urn:fdc:one-login:2023:HASH=';
        $id       = '12345-1234-1234-1234-12345';

        $this->dynamoDbClientProphecy->query(
            Argument::that(function (array $data) use ($identity) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);
                $this->assertArrayHasKey('IndexName', $data);
                $this->assertEquals('IdentityIndex', $data['IndexName']);

                $this->assertArrayHasKey('ExpressionAttributeValues', $data);
                $this->assertArrayHasKey(':sub', $data['ExpressionAttributeValues']);

                $this->assertArrayHasKey('ExpressionAttributeNames', $data);
                $this->assertArrayHasKey('#sub', $data['ExpressionAttributeNames']);

                $this->assertEquals(['S' => $identity], $data['ExpressionAttributeValues'][':sub']);

                return true;
            })
        )->willReturn(
            $this->createAWSResult([
                'Items' => [
                    [
                        'Id' => [
                            'S' => $id,
                        ],
                    ],
                ],
            ])
        );

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $actorRepo->getByIdentity($identity);

        $this->assertEquals($id, $result['Id']);
    }

    #[Test]
    public function will_get_a_user_record_by_email(): void
    {
        $email = 'a@b.com';
        $id    = '12345-1234-1234-1234-12345';

        $this->dynamoDbClientProphecy->query(
            Argument::that(function (array $data) use ($email) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);
                $this->assertArrayHasKey('IndexName', $data);
                $this->assertEquals('EmailIndex', $data['IndexName']);

                $this->assertArrayHasKey('ExpressionAttributeValues', $data);
                $this->assertArrayHasKey(':email', $data['ExpressionAttributeValues']);

                $this->assertEquals(['S' => $email], $data['ExpressionAttributeValues'][':email']);

                return true;
            })
        )->willReturn(
            $this->createAWSResult([
                'Items' => [
                    [
                        'Id' => [
                            'S' => $id,
                        ],
                    ],
                ],
            ])
        );

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $actorRepo->getByEmail($email);

        $this->assertEquals($id, $result['Id']);
    }

    #[Test]
    public function will_fail_to_get_a_user_record_when_they_dont_exist(): void
    {
        $id = '12345-1234-1234-1234-12345';

        $this->dynamoDbClientProphecy->getItem(
            Argument::that(function (array $data) use ($id) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('Id', $data['Key']);

                $this->assertEquals(['S' => $id], $data['Key']['Id']);

                return true;
            })
        )->willReturn(
            $this->createAWSResult([
                'Item' => [],
            ])
        );

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $actorRepo->get($id);
    }

    #[Test]
    public function will_fail_to_get_a_user_record_by_email_when_it_doesnt_exist(): void
    {
        $email = 'c@d.com';

        $this->dynamoDbClientProphecy->query(
            Argument::that(function (array $data) use ($email) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('IndexName', $data);
                $this->assertEquals('EmailIndex', $data['IndexName']);

                //---

                $this->assertArrayHasKey('ExpressionAttributeValues', $data);
                $this->assertArrayHasKey(':email', $data['ExpressionAttributeValues']);

                $this->assertEquals(['S' => $email], $data['ExpressionAttributeValues'][':email']);

                return true;
            })
        )->willReturn(
            $this->createAWSResult([
                'Item' => [],
            ])
        );

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $actorRepo->getByEmail($email);
    }

    #[Test]
    public function will_fail_to_get_a_user_record_by_identity_when_it_doesnt_exist(): void
    {
        $identity = 'urn:fdc:one-login:2023:HASH=';

        $this->dynamoDbClientProphecy->query(
            Argument::that(function (array $data) use ($identity) {
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);
                $this->assertEquals('IdentityIndex', $data['IndexName']);

                $this->assertEquals('Identity', $data['ExpressionAttributeNames']['#sub']);
                $this->assertEquals(['S' => $identity], $data['ExpressionAttributeValues'][':sub']);

                return true;
            })
        )->willReturn(
            $this->createAWSResult([
                'Item' => [],
            ])
        );

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $actorRepo->getByIdentity($identity);
    }

    #[Test]
    public function will_record_a_successful_login(): void
    {
        $date = (new DateTime('now'))->format(DateTimeInterface::ATOM);

        $this->dynamoDbClientProphecy->updateItem(
            Argument::that(function (array $data) use ($date) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString(self::TABLE_NAME, serialize($data));
                $this->assertStringContainsString('test@example.com', serialize($data));
                $this->assertStringContainsString($date, serialize($data));

                return true;
            })
        )->shouldBeCalled();

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $actorRepo->recordSuccessfulLogin('test@example.com', $date);
    }

    #[Test]
    public function will_delete_a_users_account(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $email    = 'a@b.com';
        $identity = 'urn:fdc:one-login:2023:HASH=';

        $this->dynamoDbClientProphecy
            ->getItem(Argument::any())
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'Id'       => ['S' => $id],
                    'Email'    => ['S' => $email],
                    'Identity' => ['S' => $identity],
                ],
            ]));

        $this->dynamoDbClientProphecy
            ->transactWriteItems([
                'TransactItems' => [
                    [
                        'Delete' => [
                            'TableName' => self::TABLE_NAME,
                            'Key'       => ['Id' => ['S' => $id]],
                        ],
                    ],
                    [
                        'Delete' => [
                            'TableName' => self::TABLE_NAME,
                            'Key'       => ['Id' => ['S' => 'EMAIL#' . $email]],
                        ],
                    ],
                    [
                        'Delete' => [
                            'TableName' => self::TABLE_NAME,
                            'Key'       => ['Id' => ['S' => 'IDENTITY#' . $identity]],
                        ],
                    ],
                ],
            ])
            ->shouldBeCalled();

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $deletedUser = $actorRepo->delete($id);

        $this->assertEquals($id, $deletedUser['Id']);
        $this->assertEquals($email, $deletedUser['Email']);
    }

    #[Test]
    public function will_delete_a_pre_onelogin_users_account(): void
    {
        $id    = '12345-1234-1234-1234-12345';
        $email = 'a@b.com';

        $this->dynamoDbClientProphecy
            ->getItem(Argument::any())
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'Id'    => ['S' => $id],
                    'Email' => ['S' => $email],
                ],
            ]));

        $this->dynamoDbClientProphecy
            ->transactWriteItems([
                'TransactItems' => [
                    [
                        'Delete' => [
                            'TableName' => self::TABLE_NAME,
                            'Key'       => ['Id' => ['S' => $id]],
                        ],
                    ],
                    [
                        'Delete' => [
                            'TableName' => self::TABLE_NAME,
                            'Key'       => ['Id' => ['S' => 'EMAIL#' . $email]],
                        ],
                    ],
                ],
            ])
            ->shouldBeCalled();

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $deletedUser = $actorRepo->delete($id);

        $this->assertEquals($id, $deletedUser['Id']);
        $this->assertEquals($email, $deletedUser['Email']);
    }

    #[Test]
    public function will_throw_error_if_account_id_to_delete_doesnt_exist(): void
    {
        $id       = 'd0E2nT-ex12t';
        $email    = 'email@example.com';
        $identity = 'urn:whatever';

        $this->dynamoDbClientProphecy
            ->getItem(Argument::any())
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'Id'       => ['S' => $id],
                    'Email'    => ['S' => $email],
                    'Identity' => ['S' => $identity],
                ],
            ]));
        $this->dynamoDbClientProphecy
            ->transactWriteItems([
                'TransactItems' => [
                    [
                        'Delete' => [
                            'TableName' => 'users-table',
                            'Key'       => ['Id' => ['S' => $id]],
                        ],
                    ],
                    [
                        'Delete' => [
                            'TableName' => 'users-table',
                            'Key'       => ['Id' => ['S' => 'EMAIL#' . $email]],
                        ],
                    ],
                    [
                        'Delete' => [
                            'TableName' => 'users-table',
                            'Key'       => ['Id' => ['S' => 'IDENTITY#' . $identity]],
                        ],
                    ],
                ],
            ])
            ->willThrow(new NotFoundException());

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), 'users-table');

        $this->expectException(NotFoundException::class);
        $actorRepo->delete($id);
    }

    #[Test]
    public function will_change_a_users_email(): void
    {
        $this->dynamoDbClientProphecy
            ->transactWriteItems([
                'TransactItems' => [
                    [
                        'Update' => [
                            'TableName'                 => self::TABLE_NAME,
                            'Key'                       => ['Id' => ['S' => 'fakeId']],
                            'UpdateExpression'          => 'SET Email=:p REMOVE EmailResetToken, EmailResetExpiry, NewEmail',
                            'ExpressionAttributeValues' => [
                                ':p' => [
                                    'S' => 'newemail@example.com',
                                ],
                            ],
                        ],
                    ],
                    [
                        'Delete' => [
                            'TableName' => self::TABLE_NAME,
                            'Key'       => [
                                'Id' => ['S' => 'EMAIL#oldemail@example.com'],
                            ],
                        ],
                    ],
                    [
                        'Put' => [
                            'TableName'           => self::TABLE_NAME,
                            'ConditionExpression' => 'attribute_not_exists(Id)',
                            'Item'                => [
                                'Id' => ['S' => 'EMAIL#newemail@example.com'],
                            ],
                        ],
                    ],
                ],
            ])->shouldBeCalled();

        $sut = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $sut->changeEmail('fakeId', 'oldemail@example.com', 'newemail@example.com');
    }

    #[Test]
    public function will_change_a_users_email_with_tracking(): void
    {
        $this->dynamoDbClientProphecy
            ->transactWriteItems([
                'TransactItems' => [
                    [
                        'Update' => [
                            'TableName'                 => self::TABLE_NAME,
                            'Key'                       => ['Id' => ['S' => 'fakeId']],
                            'UpdateExpression'          => 'SET Email=:p REMOVE EmailResetToken, EmailResetExpiry, NewEmail',
                            'ExpressionAttributeValues' => [
                                ':p' => [
                                    'S' => 'newemail@example.com',
                                ],
                            ],
                        ],
                    ],
                    [
                        'Delete' => [
                            'TableName' => self::TABLE_NAME,
                            'Key'       => [
                                'Id' => ['S' => 'EMAIL#oldemail@example.com'],
                            ],
                        ],
                    ],
                    [
                        'Put' => [
                            'TableName'           => self::TABLE_NAME,
                            'ConditionExpression' => 'attribute_not_exists(Id)',
                            'Item'                => [
                                'Id' => ['S' => 'EMAIL#newemail@example.com'],
                            ],
                        ],
                    ],
                    [
                        'Update' => [
                            'TableName'                 => self::TABLE_NAME,
                            'Key'                       => [
                                'Id' => ['S' => '#OLDEMAILS'],
                            ],
                            'UpdateExpression'          => 'ADD Emails :Email',
                            'ExpressionAttributeValues' => [
                                ':Email' => [
                                    'SS' => ['oldemail@example.com=' . hash('sha256', 'oldemail@example.com')],
                                ],
                            ],
                        ],
                    ],
                ],
            ])->shouldBeCalled();

        $sut = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME, true);

        $sut->changeEmail('fakeId', 'oldemail@example.com', 'newemail@example.com');
    }

    #[Test]
    public function will_migrate_a_local_account_to_oidc(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $identity = 'sub:gov.uk:identity';

        $this->dynamoDbClientProphecy->getItem(Argument::any())
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
            ]));

        $this->dynamoDbClientProphecy
            ->transactWriteItems([
                'TransactItems' => [
                    [
                        'Update' => [
                            'TableName'                 => 'users-table',
                            'Key'                       => ['Id' => ['S' => $id]],
                            'UpdateExpression'          => 'SET #sub = :sub REMOVE ActivationToken, ExpiresTTL, PasswordResetToken, '
                            . 'PasswordResetExpiry, NeedsReset',
                            'ExpressionAttributeValues' => [
                                ':sub' => ['S' => $identity],
                            ],
                            'ExpressionAttributeNames'  => [
                                '#sub' => 'Identity',
                            ],
                        ],
                    ],
                    [
                        'Put' => [
                            'TableName'           => 'users-table',
                            'ConditionExpression' => 'attribute_not_exists(Id)',
                            'Item'                => [
                                'Id' => ['S' => 'IDENTITY#' . $identity],
                            ],
                        ],
                    ],
                ],
            ])
            ->shouldBeCalled();

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), 'users-table');

        $user = $actorRepo->migrateToOAuth(['Id' => $id], $identity);

        $this->assertEquals($id, $user['Id']);
        $this->assertEquals($identity, $user['Identity']);
    }
}
