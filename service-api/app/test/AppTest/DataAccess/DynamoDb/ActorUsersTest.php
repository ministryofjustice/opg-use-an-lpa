<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorUsers;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use Aws\Command;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\Result;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;

#[CoversClass(ActorUsers::class)]
class ActorUsersTest extends TestCase
{
    use GenerateAwsResultTrait;
    use ProphecyTrait;

    public const string TABLE_NAME = 'test-table-name';

    /**
     * @var ObjectProphecy<DynamoDbClient>
     */
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
                        'Id'     => ['S' => 'IDENTITY#' . $identity],
                        'UserId' => ['S' => $id],
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
    #[DataProvider('orphanPins')]
    public function it_should_take_over_an_orphan_identity_pin_when_adding_a_user(
        array $pinResult,
        string $expectedCondition,
        array $expectedValues,
        array $expectedExtraItems,
    ): void {
        $id       = '12345-1234-1234-1234-12345';
        $identity = '67890-6789-6789-6789-67890';

        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'getItem',
                    $this->createAWSResult($pinResult),
                ],
                [
                    'transactWriteItems',
                    $this->createAWSResult(['@metadata' => ['statusCode' => 200]]),
                ],
            ],
            $calls,
        );

        (new ActorUsers($client, self::TABLE_NAME))->add($id, 'a@b.com', $identity, 'now', true);

        self::assertSame(
            [
                'TableName'      => self::TABLE_NAME,
                'Key'            => ['Id' => ['S' => 'IDENTITY#' . $identity]],
                'ConsistentRead' => true,
            ],
            $calls[0],
        );

        $pin = [
            'Put' => [
                'TableName'           => self::TABLE_NAME,
                'Item'                => [
                    'Id'     => ['S' => 'IDENTITY#' . $identity],
                    'UserId' => ['S' => $id],
                ],
                'ConditionExpression' => $expectedCondition,
            ] + ($expectedValues === [] ? [] : ['ExpressionAttributeValues' => $expectedValues]),
        ];

        self::assertSame([$pin, ...$expectedExtraItems], array_slice($calls[1]['TransactItems'], 1));
    }

    public static function orphanPins(): array
    {
        return [
            'no pin'                   => [
                [],
                'attribute_not_exists(Id)',
                [],
                [],
            ],
            'legacy pin without owner' => [
                ['Item' => ['Id' => ['S' => 'IDENTITY#67890-6789-6789-6789-67890']]],
                'attribute_exists(Id) AND attribute_not_exists(UserId)',
                [],
                [],
            ],
            'pin with missing owner'   => [
                [
                    'Item' => [
                        'Id'     => ['S' => 'IDENTITY#x'],
                        'UserId' => ['S' => 'gone'],
                    ],
                ],
                'UserId = :previous',
                [':previous' => ['S' => 'gone']],
                [
                    [
                        'ConditionCheck' => [
                            'TableName'           => self::TABLE_NAME,
                            'Key'                 => ['Id' => ['S' => 'gone']],
                            'ConditionExpression' => 'attribute_not_exists(Id)',
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    public function it_should_report_a_conflict_when_the_orphan_owner_check_fails(): void
    {
        $exception = new DynamoDbException('', new Command('TransactWriteItems'), [
            'code' => 'TransactionCanceledException',
            'body' => [
                'CancellationReasons' => [
                    ['Code' => 'None'],
                    ['Code' => 'None'],
                    ['Code' => 'ConditionalCheckFailed'],
                ],
            ],
        ]);

        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'getItem',
                    $this->createAWSResult(['Item' => ['Id' => ['S' => 'x'], 'UserId' => ['S' => 'o']]]),
                ],
                [
                    'transactWriteItems',
                    $exception,
                ],
            ],
            $calls,
        );

        $this->expectException(ConflictException::class);

        (new ActorUsers($client, self::TABLE_NAME))->add('id', 'a@b.com', 'subject', 'now', true);
    }

    #[Test]
    public function will_throw_exception_when_adding_a_new_user_that_doesnt_succeed(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $email    = 'a@b.com';
        $identity = 'urn:fdc:one-login:2023:HASH=';

        // status code is unexpected
        $this->dynamoDbClientProphecy->transactWriteItems(Argument::any())
            ->willReturn($this->createAWSResult(['@metadata' => ['statusCode' => 500]]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(CreationException::class);
        $this->expectExceptionMessage('Failed to create account with code');

        $actorRepo->add($id, $email, $identity, 'now');
    }

    #[Test]
    public function it_should_report_an_identity_conflict_when_creation_is_cancelled(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $email    = 'a@b.com';
        $identity = 'urn:fdc:one-login:2023:HASH=';

        $exception = new DynamoDbException('', new Command('TransactWriteItems'), [
            'code' => 'TransactionCanceledException',
            'body' => [
                'CancellationReasons' => [
                    ['Code' => 'None'],
                    ['Code' => 'ConditionalCheckFailed'],
                ],
            ],
        ]);
        $client    = $this->getMockBuilder(DynamoDbClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $client->expects(self::once())->method('__call')
            ->with('transactWriteItems', self::anything())->willThrowException($exception);

        $actorRepo = new ActorUsers($client, self::TABLE_NAME);

        try {
            $actorRepo->add($id, $email, $identity, 'now');
            self::fail('Expected an identity conflict');
        } catch (ConflictException $ex) {
            self::assertSame(['identity' => $identity], $ex->getAdditionalData());
            self::assertSame($exception, $ex->getPrevious());
        }
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
    public function it_should_get_the_user_that_owns_the_identity_pin(): void
    {
        $identity = 'urn:fdc:one-login:2023:HASH=';
        $id       = '12345-1234-1234-1234-12345';

        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'getItem',
                    $this->createAWSResult([
                        'Item' => [
                            'Id'     => ['S' => 'IDENTITY#' . $identity],
                            'UserId' => ['S' => $id],
                        ],
                    ]),
                ],
                [
                    'getItem',
                    $this->createAWSResult(['Item' => ['Id' => ['S' => $id]]]),
                ],
            ],
            $calls,
        );

        $result = (new ActorUsers($client, self::TABLE_NAME))->getByIdentity($identity);

        self::assertSame(['Id' => $id], $result);
        self::assertSame(['Id' => ['S' => $id]], $calls[1]['Key']);
        self::assertTrue($calls[1]['ConsistentRead']);
    }

    #[Test]
    #[DataProvider('unownedPins')]
    public function it_should_find_a_user_by_index_and_record_them_as_the_pin_owner(
        array $pinResult,
        string $expectedCondition,
        array $expectedValues,
    ): void {
        $identity = 'urn:fdc:one-login:2023:HASH=';
        $id       = '12345-1234-1234-1234-12345';

        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'getItem',
                    $this->createAWSResult($pinResult),
                ],
                ...(isset($pinResult['Item']['UserId']) ? [['getItem', $this->createAWSResult([])]] : []),
                [
                    'query',
                    $this->createAWSResult(['Items' => [['Id' => ['S' => $id]]]]),
                ],
                [
                    'updateItem',
                    $this->createAWSResult([]),
                ],
            ],
            $calls,
        );

        $result = (new ActorUsers($client, self::TABLE_NAME))->getByIdentity($identity);

        self::assertSame(['Id' => $id], $result);

        $query = $calls[array_key_last($calls) - 1];
        self::assertSame('IdentityIndex', $query['IndexName']);
        self::assertSame(['S' => $identity], $query['ExpressionAttributeValues'][':sub']);

        $update = $calls[array_key_last($calls)];
        self::assertSame(['Id' => ['S' => 'IDENTITY#' . $identity]], $update['Key']);
        self::assertSame('SET UserId = :owner', $update['UpdateExpression']);
        self::assertSame($expectedCondition, $update['ConditionExpression']);
        self::assertSame([':owner' => ['S' => $id]] + $expectedValues, $update['ExpressionAttributeValues']);
    }

    public static function unownedPins(): array
    {
        return [
            'no pin'                   => [
                [],
                'attribute_not_exists(UserId)',
                [],
            ],
            'legacy pin without owner' => [
                ['Item' => ['Id' => ['S' => 'IDENTITY#x']]],
                'attribute_not_exists(UserId)',
                [],
            ],
            'pin with missing owner'   => [
                [
                    'Item' => [
                        'Id'     => ['S' => 'IDENTITY#x'],
                        'UserId' => ['S' => 'gone'],
                    ],
                ],
                'UserId = :previous',
                [':previous' => ['S' => 'gone']],
            ],
        ];
    }

    #[Test]
    public function it_should_refuse_to_choose_between_duplicate_identity_holders(): void
    {
        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'getItem',
                    $this->createAWSResult([]),
                ],
                [
                    'query',
                    $this->createAWSResult([
                        'Items' => [
                            ['Id' => ['S' => 'first']],
                            ['Id' => ['S' => 'second']],
                        ],
                    ]),
                ],
            ],
            $calls,
        );

        try {
            (new ActorUsers($client, self::TABLE_NAME))->getByIdentity('subject');
            self::fail('Expected a ConflictException');
        } catch (ConflictException $e) {
            self::assertSame(
                [
                    'identity' => 'subject',
                    'users'    => [
                        'first',
                        'second',
                    ],
                ],
                $e->getAdditionalData(),
            );
        }

        self::assertCount(2, $calls, 'the pin must not be repointed');
    }

    #[Test]
    public function it_should_use_the_pin_owner_even_when_duplicates_exist(): void
    {
        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'getItem',
                    $this->createAWSResult(['Item' => ['Id' => ['S' => 'IDENTITY#subject'], 'UserId' => ['S' => 'a']]]),
                ],
                [
                    'getItem',
                    $this->createAWSResult(['Item' => ['Id' => ['S' => 'a']]]),
                ],
            ],
            $calls,
        );

        self::assertSame('a', (new ActorUsers($client, self::TABLE_NAME))->getByIdentity('subject')['Id']);
    }

    #[Test]
    public function it_should_ignore_losing_the_race_to_record_the_pin_owner(): void
    {
        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'getItem',
                    $this->createAWSResult([]),
                ],
                [
                    'query',
                    $this->createAWSResult(['Items' => [['Id' => ['S' => 'id']]]]),
                ],
                [
                    'updateItem',
                    new DynamoDbException(
                        '',
                        new Command('UpdateItem'),
                        ['code' => 'ConditionalCheckFailedException'],
                    ),
                ],
            ],
            $calls,
        );

        self::assertSame('id', (new ActorUsers($client, self::TABLE_NAME))->getByIdentity('subject')['Id']);
    }

    #[Test]
    public function it_should_not_swallow_other_failures_recording_the_pin_owner(): void
    {
        $exception = new DynamoDbException(
            '',
            new Command('UpdateItem'),
            ['code' => 'ProvisionedThroughputExceededException'],
        );

        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'getItem',
                    $this->createAWSResult([]),
                ],
                [
                    'query',
                    $this->createAWSResult(['Items' => [['Id' => ['S' => 'id']]]]),
                ],
                [
                    'updateItem',
                    $exception,
                ],
            ],
            $calls,
        );

        $this->expectExceptionObject($exception);

        (new ActorUsers($client, self::TABLE_NAME))->getByIdentity('subject');
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

        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'getItem',
                    $this->createAWSResult([]),
                ],
                [
                    'query',
                    $this->createAWSResult(['Items' => []]),
                ],
            ],
            $calls,
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        (new ActorUsers($client, self::TABLE_NAME))->getByIdentity($identity);
    }

    /**
     * @param list<array{0: string, 1: Result|Throwable}> $script the expected calls, in order, with their outcome
     * @param list<array> $calls receives the arguments of each call made
     */
    private function scriptedClient(array $script, array &$calls): DynamoDbClient
    {
        $client = $this->getMockBuilder(DynamoDbClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();

        $client->expects(self::exactly(count($script)))
            ->method('__call')
            ->willReturnCallback(
                function (string $name, array $arguments) use (&$script, &$calls): Result {
                    [
                        $expected,
                        $outcome,
                    ] = array_shift($script);

                    self::assertSame($expected, $name);
                    $calls[] = $arguments[0];

                    if ($outcome instanceof Throwable) {
                        throw $outcome;
                    }

                    return $outcome;
                }
            );

        return $client;
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
            ->updateItem(
                [
                    'TableName'                 => self::TABLE_NAME,
                    'Key'                       => ['Id' => ['S' => 'fakeId']],
                    'UpdateExpression'          => 'SET Email=:p',
                    'ExpressionAttributeValues' => [
                        ':p' => [
                            'S' => 'newemail@example.com',
                        ],
                    ],
                ],
            )->shouldBeCalled();

        $sut = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $sut->changeEmail('fakeId', 'oldemail@example.com', 'newemail@example.com');
    }

    #[Test]
    public function it_should_migrate_the_existing_account_atomically(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $identity = 'sub:gov.uk:identity';

        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'transactWriteItems',
                    $this->createAWSResult(['@metadata' => ['statusCode' => 200]]),
                ],
            ],
            $calls,
        );

        $original = [
            'Id'        => $id,
            'Email'     => 'user@example.com',
            'LastLogin' => '2026-11-01T12:00:00+00:00',
        ];

        $user = (new ActorUsers($client, 'users-table'))->migrateToOAuth($original, $identity);

        self::assertSame($original + ['Identity' => $identity], $user);
        self::assertSame(
            [
                'TransactItems' => [
                    [
                        'Update' => [
                            'TableName'                 => 'users-table',
                            'Key'                       => ['Id' => ['S' => $id]],
                            'UpdateExpression'          => 'SET #sub = :sub',
                            'ConditionExpression'       => 'attribute_exists(Id) AND attribute_not_exists(#sub)',
                            'ExpressionAttributeValues' => [':sub' => ['S' => $identity]],
                            'ExpressionAttributeNames'  => ['#sub' => 'Identity'],
                        ],
                    ],
                    [
                        'Put' => [
                            'TableName'           => 'users-table',
                            'Item'                => [
                                'Id'     => ['S' => 'IDENTITY#' . $identity],
                                'UserId' => ['S' => $id],
                            ],
                            'ConditionExpression' => 'attribute_not_exists(Id)',
                        ],
                    ],
                ],
            ],
            $calls[0],
        );
    }

    #[Test]
    public function it_should_take_over_an_orphan_identity_pin_when_migrating(): void
    {
        $calls  = [];
        $client = $this->scriptedClient(
            [
                [
                    'getItem',
                    $this->createAWSResult(['Item' => ['Id' => ['S' => 'IDENTITY#s'], 'UserId' => ['S' => 'gone']]]),
                ],
                [
                    'transactWriteItems',
                    $this->createAWSResult(['@metadata' => ['statusCode' => 200]]),
                ],
            ],
            $calls,
        );

        (new ActorUsers($client, 'users-table'))
            ->migrateToOAuth(['Id' => 'existing', 'Email' => 'user@example.com'], 's', true);

        $items = $calls[1]['TransactItems'];

        self::assertCount(3, $items);
        self::assertSame('UserId = :previous', $items[1]['Put']['ConditionExpression']);
        self::assertSame(['S' => 'existing'], $items[1]['Put']['Item']['UserId']);
        self::assertSame(['Id' => ['S' => 'gone']], $items[2]['ConditionCheck']['Key']);
    }

    #[Test]
    public function it_should_report_a_conflict_when_the_account_is_no_longer_eligible_for_migration(): void
    {
        $exception = new DynamoDbException('', new Command('TransactWriteItems'), [
            'code' => 'TransactionCanceledException',
            'body' => [
                'CancellationReasons' => [
                    ['Code' => 'ConditionalCheckFailed'],
                    ['Code' => 'None'],
                ],
            ],
        ]);

        $calls  = [];
        $client = $this->scriptedClient([['transactWriteItems', $exception]], $calls);

        try {
            (new ActorUsers($client, self::TABLE_NAME))
                ->migrateToOAuth(['Id' => 'existing-id', 'Email' => 'user@example.com'], 'subject');
            self::fail('Expected a conflict');
        } catch (ConflictException $ex) {
            self::assertSame(['user' => 'existing-id'], $ex->getAdditionalData());
            self::assertSame($exception, $ex->getPrevious());
        }
    }

    #[Test]
    public function it_should_report_an_identity_conflict_when_migration_is_cancelled(): void
    {
        $exception = new DynamoDbException('', new Command('TransactWriteItems'), [
            'code' => 'TransactionCanceledException',
            'body' => [
                'CancellationReasons' => [
                    ['Code' => 'None'],
                    ['Code' => 'ConditionalCheckFailed'],
                ],
            ],
        ]);

        $client = $this->getMockBuilder(DynamoDbClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $client->expects(self::once())->method('__call')
            ->with('transactWriteItems', self::anything())->willThrowException($exception);

        $repository = new ActorUsers($client, self::TABLE_NAME);

        try {
            $repository->migrateToOAuth(['Id' => 'existing-id', 'Email' => 'user@example.com'], 'subject');
            self::fail('Expected an identity conflict');
        } catch (ConflictException $ex) {
            self::assertSame(['identity' => 'subject'], $ex->getAdditionalData());
            self::assertSame($exception, $ex->getPrevious());
        }
    }

    #[Test]
    #[DataProvider('nonIdentityFailures')]
    public function it_should_not_classify_other_creation_failures_as_orphans(string $code, array $body): void
    {
        $exception = new DynamoDbException('', new Command('TransactWriteItems'), ['code' => $code, 'body' => $body]);

        $client = $this->getMockBuilder(DynamoDbClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $client->expects(self::once())->method('__call')
            ->with('transactWriteItems', self::anything())->willThrowException($exception);

        $repository = new ActorUsers($client, self::TABLE_NAME);

        try {
            $repository->add('id', 'user@example.com', 'subject', 'now');
            self::fail('Expected creation failure');
        } catch (CreationException $ex) {
            self::assertSame($exception, $ex->getPrevious());
        }
    }

    #[Test]
    #[DataProvider('nonIdentityMigrationFailures')]
    public function it_should_not_classify_other_migration_failures_as_orphans(string $code, array $body): void
    {
        $exception = new DynamoDbException('', new Command('TransactWriteItems'), ['code' => $code, 'body' => $body]);

        $client = $this->getMockBuilder(DynamoDbClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $client->expects(self::once())->method('__call')
            ->with('transactWriteItems', self::anything())->willThrowException($exception);

        $repository = new ActorUsers($client, self::TABLE_NAME);

        try {
            $repository->migrateToOAuth(['Id' => 'id', 'Email' => 'user@example.com'], 'subject');
            self::fail('Expected migration failure');
        } catch (CreationException $ex) {
            self::assertSame($exception, $ex->getPrevious());
        }
    }

    public static function nonIdentityMigrationFailures(): array
    {
        // a failed first operation is, for migrations, the account no longer being eligible
        return array_diff_key(self::nonIdentityFailures(), ['different operation failed' => true]);
    }

    public static function nonIdentityFailures(): array
    {
        return [
            'unrelated AWS error'        => [
                'ProvisionedThroughputExceededException',
                [],
            ],
            'missing reasons'            => [
                'TransactionCanceledException',
                [],
            ],
            'different operation failed' => [
                'TransactionCanceledException',
                [
                    'CancellationReasons' => [
                        ['Code' => 'ConditionalCheckFailed'],
                        ['Code' => 'None'],
                    ],
                ],
            ],
            'pin transaction conflict'   => [
                'TransactionCanceledException',
                [
                    'CancellationReasons' => [
                        ['Code' => 'None'],
                        ['Code' => 'TransactionConflict'],
                    ],
                ],
            ],
            'both operations failed'     => [
                'TransactionCanceledException',
                [
                    'CancellationReasons' => [
                        ['Code' => 'ProvisionedThroughputExceeded'],
                        ['Code' => 'ConditionalCheckFailed'],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('unsuccessfulMigrationResponses')]
    public function it_should_reject_unsuccessful_migration_responses(array $result): void
    {
        $client = $this->getMockBuilder(DynamoDbClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $client->expects(self::once())->method('__call')
            ->with('transactWriteItems', self::anything())->willReturn(new Result($result));

        $repository = new ActorUsers($client, self::TABLE_NAME);

        $this->expectException(CreationException::class);
        $this->expectExceptionMessage('Failed to migrate account with code');

        $repository->migrateToOAuth(['Id' => 'id', 'Email' => 'user@example.com'], 'subject');
    }

    public static function unsuccessfulMigrationResponses(): array
    {
        return [
            'non-OK status'  => [['@metadata' => ['statusCode' => 500]]],
            'missing status' => [[]],
        ];
    }
}
