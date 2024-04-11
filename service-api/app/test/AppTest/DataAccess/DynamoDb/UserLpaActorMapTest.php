<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\UserLpaActorMap;
use Aws\CommandInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class UserLpaActorMapTest extends TestCase
{
    use GenerateAwsResultTrait;
    use ProphecyTrait;

    public const TABLE_NAME = 'test-table-name';

    private DynamoDbClient|ObjectProphecy $dynamoDbClientProphecy;

    public function assertIsValidUuid($uuid, string $message = '')
    {
        $pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        self::assertMatchesRegularExpression($pattern, $uuid, $message);
    }

    protected function setUp(): void
    {
        $this->dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
    }

    //--------------------------------------------------------------------------------
    // Get

    /** @test */
    public function can_lookup_a_token(): void
    {
        $testToken     = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId    = 'test-user-id';
        $testActorId   = 1;
        $testAdded     = gmdate('c');

        $this->dynamoDbClientProphecy->getItem(Argument::that(function (array $data) use ($testToken) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            //---

            $this->assertArrayHasKey('Key', $data);
            $this->assertArrayHasKey('Id', $data['Key']);

            $this->assertEquals(['S' => $testToken], $data['Key']['Id']);

            return true;
        }))
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'Id'        => [
                        'S' => $testToken,
                    ],
                    'SiriusUid' => [
                        'S' => $testSiriusUid,
                    ],
                    'UserId'    => [
                        'S' => $testUserId,
                    ],
                    'ActorId'   => [
                        'N' => $testActorId,
                    ],
                    'Added'     => [
                        'S' => $testAdded,
                    ],
                ],
            ]))->shouldBeCalled();

        $repo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $result = $repo->get($testToken);

        $this->assertEquals($testToken, $result['Id']);
        $this->assertEquals($testSiriusUid, $result['SiriusUid']);
        $this->assertEquals($testUserId, $result['UserId']);
        $this->assertInstanceOf(DateTime::class, $result['Added']);
        $this->assertEquals($testAdded, $result['Added']->format('c'));
        $this->assertEquals($testActorId, $result['ActorId']);
    }

    /** @test */
    public function cannot_lookup_a_missing_code(): void
    {
        $testToken = 'test-token';

        $this->dynamoDbClientProphecy->getItem(Argument::that(function (array $data) use ($testToken) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            //---

            $this->assertArrayHasKey('Key', $data);
            $this->assertArrayHasKey('Id', $data['Key']);

            $this->assertEquals(['S' => $testToken], $data['Key']['Id']);

            return true;
        }))
            ->willReturn($this->createAWSResult([
                'Item' => [],
            ]))->shouldBeCalled();

        $repo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $result = $repo->get($testToken);

        // Null is returned on a Not Found

        $this->assertNull($result);
    }

    //--------------------------------------------------------------------------------
    // Create

    /** @test */
    public function add_unique_token(): void
    {
        $testSiriusUid = 'test-uid';
        $testUserId    = 'test-user-id';
        $testActorId   = 1;
        $testCode      = 'test-code';

        $this->dynamoDbClientProphecy->putItem(Argument::that(function (array $data) use (
            $testSiriusUid,
            $testUserId,
            $testActorId,
            $testCode
        ) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            //---

            $this->assertArrayHasKey('TableName', $data);
            $this->assertArrayHasKey('Item', $data);
            $this->assertArrayHasKey('ConditionExpression', $data);
            $this->assertArrayNotHasKey('ActivateBy', $data);

            $this->assertEquals('attribute_not_exists(Id)', $data['ConditionExpression']);

            $this->assertIsValidUuid($data['Item']['Id']['S']);
            $this->assertEquals(['S' => $testUserId], $data['Item']['UserId']);
            $this->assertIsString($data['Item']['UserId']['S']);
            $this->assertEquals(['S' => $testSiriusUid], $data['Item']['SiriusUid']);
            $this->assertIsString($data['Item']['SiriusUid']['S']);
            $this->assertEquals(['N' => $testActorId], $data['Item']['ActorId']);
            $this->assertIsString($data['Item']['ActorId']['N']);
            $this->assertEquals(['S' => $testCode], $data['Item']['ActivationCode']);
            $this->assertIsString($data['Item']['ActivationCode']['S']);

            // Checks 'now' is correct, we a little bit of leeway
            $this->assertEqualsWithDelta(time(), strtotime($data['Item']['Added']['S']), 5);

            return true;
        }))->shouldBeCalled();

        $repo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $repo->create($testUserId, $testSiriusUid, (string)$testActorId, null, null, $testCode);
    }

    /** @test */
    public function add_unique_token_without_optional_values(): void
    {
        $testSiriusUid = 'test-uid';
        $testUserId    = 'test-user-id';

        $this->dynamoDbClientProphecy->putItem(Argument::that(function (array $data) use (
            $testSiriusUid,
            $testUserId
        ) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            //---

            $this->assertArrayHasKey('TableName', $data);
            $this->assertArrayHasKey('Item', $data);
            $this->assertArrayHasKey('ConditionExpression', $data);
            $this->assertArrayNotHasKey('ActivateBy', $data);
            $this->assertArrayNotHasKey('ActorId', $data);
            $this->assertArrayNotHasKey('ActivationCode', $data);

            $this->assertEquals('attribute_not_exists(Id)', $data['ConditionExpression']);

            $this->assertIsValidUuid($data['Item']['Id']['S']);
            $this->assertEquals(['S' => $testUserId], $data['Item']['UserId']);
            $this->assertIsString($data['Item']['UserId']['S']);
            $this->assertEquals(['S' => $testSiriusUid], $data['Item']['SiriusUid']);
            $this->assertIsString($data['Item']['SiriusUid']['S']);

            // Checks 'now' is correct, we a little bit of leeway
            $this->assertEqualsWithDelta(time(), strtotime($data['Item']['Added']['S']), 5);

            return true;
        }))->shouldBeCalled();

        $repo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $repo->create($testUserId, $testSiriusUid, null);
    }

    /** @test */
    public function add_unique_token_with_TTL(): void
    {
        $yearInEpoch   = 31536000;
        $testSiriusUid = 'test-uid';
        $testUserId    = 'test-user-id';
        $testActorId   = 1;

        $this->dynamoDbClientProphecy->putItem(Argument::that(function (array $data) use (
            $yearInEpoch,
            $testSiriusUid,
            $testUserId,
            $testActorId
        ) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            //---

            $this->assertArrayHasKey('TableName', $data);
            $this->assertArrayHasKey('Item', $data);
            $this->assertArrayHasKey('ConditionExpression', $data);

            $this->assertEquals('attribute_not_exists(Id)', $data['ConditionExpression']);

            $this->assertIsValidUuid($data['Item']['Id']['S']);
            $this->assertEquals(['S' => $testUserId], $data['Item']['UserId']);
            $this->assertIsString($data['Item']['UserId']['S']);
            $this->assertEquals(['S' => $testSiriusUid], $data['Item']['SiriusUid']);
            $this->assertIsString($data['Item']['SiriusUid']['S']);
            $this->assertEquals(['N' => $testActorId], $data['Item']['ActorId']);
            $this->assertIsString($data['Item']['ActorId']['N']);

            // Checks 'now' is correct, with a little bit of leeway
            $this->assertEqualsWithDelta(time(), strtotime($data['Item']['Added']['S']), 5);

            $this->assertEqualsWithDelta(time() + $yearInEpoch, $data['Item']['ActivateBy']['N'], 5);

            return true;
        }))->shouldBeCalled();

        $repo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $repo->create(
            $testUserId,
            $testSiriusUid,
            (string)$testActorId,
            new DateInterval('P365D'),
            new DateInterval('P2W')
        );
    }

    /** @test */
    public function add_conflicting_code(): void
    {
        /** @var MockObject|DynamoDbClient $dDBMock */
        $dDBMock = $this->createMock(DynamoDbClient::class);

        $dDBMock
            ->expects($this->exactly(2))
            ->method('__call')
            ->with($this->identicalTo('putItem'), $this->anything())
            ->willReturnOnConsecutiveCalls(
                $this->throwException(
                    new DynamoDbException(
                        'exception',
                        $this->prophesize(CommandInterface::class)->reveal(),
                        ['code' => 'ConditionalCheckFailedException']
                    )
                ),
                '',
            );

        //---

        $repo = new UserLpaActorMap(
            $dDBMock,
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $id = $repo->create('test-val', 'test-val', 'test-val');

        $this->assertIsValidUuid($id);
    }

    /** @test */
    public function unknown_exception_when_adding_code(): void
    {
        $this->dynamoDbClientProphecy->putItem(Argument::any())
            ->willThrow(DynamoDbException::class)
            ->shouldBeCalled();

        //---

        $repo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        // We should now expect a DynamoDbException
        $this->expectException(DynamoDbException::class);

        $repo->create('test-val', 'test-val', 'test-val');
    }

    //--------------------------------------------------------------------------------
    // Delete

    /** @test */
    public function can_delete_map(): void
    {
        $testToken     = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId    = 'test-user-id';
        $testActorId   = 1;
        $testAdded     = gmdate('c');

        $this->dynamoDbClientProphecy->deleteItem(Argument::that(function (array $data) use ($testToken) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            $this->assertArrayHasKey('Key', $data);
            $this->assertEquals(['Id' => ['S' => $testToken]], $data['Key']);

            return true;
        }))->willReturn($this->createAWSResult([
            'Item' => [
                'Id'        => [
                    'S' => $testToken,
                ],
                'SiriusUid' => [
                    'S' => $testSiriusUid,
                ],
                'Added'     => [
                    'S' => $testAdded,
                ],
                'ActorId'   => [
                    'S' => (string) $testActorId,
                ],
                'UserId'    => [
                    'S' => $testUserId,
                ],
            ],
        ]));

        $userLpaActorMapRepo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $removeActorMap = $userLpaActorMapRepo->delete($testToken);

        $this->assertEquals($testToken, $removeActorMap['Id']);
    }

    //--------------------------------------------------------------------------------
    // Activate record

    /** @test */
    public function can_activate_record(): void
    {
        $testToken          = 'test-token';
        $testSiriusUid      = 'test-uid';
        $testUserId         = 'test-user-id';
        $testActorId        = '1';
        $testActivationCode = '8EFXFEF48WJ4';
        $testAdded          = gmdate('c');
        $testActivated      = gmdate('c');

        $this->dynamoDbClientProphecy->updateItem(Argument::that(function (array $data) use ($testToken) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            $this->assertArrayHasKey('Key', $data);
            $this->assertEquals(['Id' => ['S' => $testToken]], $data['Key']);

            $this->assertArrayHasKey('UpdateExpression', $data);
            $this->assertEquals(
                'set ActorId = :a, ActivationCode = :b, ActivatedOn = :c remove ActivateBy, DueBy',
                $data['UpdateExpression']
            );

            return true;
        }))->willReturn($this->createAWSResult([
            'Item' => [
                'Id'          => [
                    'S' => $testToken,
                ],
                'SiriusUid'   => [
                    'S' => $testSiriusUid,
                ],
                'Added'       => [
                    'S' => $testAdded,
                ],
                'ActorId'     => [
                    'S' => $testActorId,
                ],
                'UserId'      => [
                    'S' => $testUserId,
                ],
                'ActivatedOn' => [
                    'S' => $testActivated,
                ],
            ],
        ]));

        $userLpaActorMapRepo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $removeActorMap = $userLpaActorMapRepo->activateRecord($testToken, $testActorId, $testActivationCode);
        $this->assertEquals($testToken, $removeActorMap['Id']);
    }

    //--------------------------------------------------------------------------------
    // Get All

    /** @test */
    public function can_get_all_lpas_for_user(): void
    {
        $testToken     = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId    = 'test-user-id';
        $testActorId   = 1;
        $testAdded     = gmdate('c');

        $this->dynamoDbClientProphecy->query(Argument::that(function (array $data) use ($testUserId) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            //---

            $this->assertArrayHasKey('IndexName', $data);
            $this->assertArrayHasKey('KeyConditionExpression', $data);
            $this->assertArrayHasKey('ExpressionAttributeValues', $data);

            $this->assertEquals('UserIndex', $data['IndexName']);
            $this->assertEquals('UserId = :user_id', $data['KeyConditionExpression']);

            $this->assertArrayHasKey(':user_id', $data['ExpressionAttributeValues']);
            $this->assertEquals(['S' => $testUserId], $data['ExpressionAttributeValues'][':user_id']);

            return true;
        }))->willReturn(
            $this->createAWSResult([
            'Items' => [
                [
                    'Id'        => [
                        'S' => $testToken,
                    ],
                    'SiriusUid' => [
                        'S' => $testSiriusUid,
                    ],
                    'UserId'    => [
                        'S' => $testUserId,
                    ],
                    'ActorId'   => [
                        'N' => $testActorId,
                    ],
                    'Added'     => [
                        'S' => $testAdded,
                    ],
                ],
            ],
            ])
        )->shouldBeCalled();

        $repo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $result = $repo->getByUserId($testUserId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $item = array_pop($result);

        $this->assertEquals($testToken, $item['Id']);
        $this->assertEquals($testUserId, $item['UserId']);
        $this->assertEquals($testSiriusUid, $item['SiriusUid']);
        $this->assertEquals($testActorId, $item['ActorId']);

        $this->assertInstanceOf(DateTime::class, $item['Added']);
        $this->assertEquals($testAdded, $item['Added']->format('c'));
    }

    //--------------------------------------------------------------------------------
    // Renew Activation Period

    /** @test */
    public function can_update_record(): void
    {
        $testToken     = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId    = 'test-user-id';
        $testActorId   = 1;
        $testAdded     = gmdate('c');

        $now    = new DateTimeImmutable();
        $expiry = (string) $now->add(new DateInterval('P1Y'))->getTimestamp();

        $this->dynamoDbClientProphecy->updateItem(Argument::that(function (array $data) use ($testToken, $expiry) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            $this->assertArrayHasKey('Key', $data);
            $this->assertEquals(['Id' => ['S' => $testToken]], $data['Key']);

            $this->assertArrayHasKey('UpdateExpression', $data);
            $this->assertEquals('set ActivateBy = :a, DueBy = :b, ActorId = :c', $data['UpdateExpression']);

            $this->assertArrayHasKey(':a', $data['ExpressionAttributeValues']);
            $this->assertArrayHasKey('N', $data['ExpressionAttributeValues'][':a']);
            $this->assertEqualsWithDelta($expiry, $data['ExpressionAttributeValues'][':a']['N'], 5);

            $this->assertArrayHasKey(':b', $data['ExpressionAttributeValues']);
            $this->assertArrayHasKey('S', $data['ExpressionAttributeValues'][':b']);
            return true;
        }))->willReturn($this->createAWSResult(
            [
                'Item' => [
                    'Id'         => [
                        'S' => $testToken,
                    ],
                    'SiriusUid'  => [
                        'S' => $testSiriusUid,
                    ],
                    'Added'      => [
                        'S' => $testAdded,
                    ],
                    'ActorId'    => [
                        'S' => (string)$testActorId,
                    ],
                    'UserId'     => [
                        'S' => $testUserId,
                    ],
                    'ActivateBy' => [
                        'N' => $expiry,
                    ],
                    'DueBy'      => [
                        'S' => 'P2W',
                    ],
                ],
            ]
        ));

        $userLpaActorMapRepo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $renew = $userLpaActorMapRepo->updateRecord(
            $testToken,
            new DateInterval('P1Y'),
            new DateInterval('P2W'),
            (string)$testActorId
        );
        $this->assertEquals($testToken, $renew['Id']);
    }

    /** @test */
    public function can_update_record_without_actorId(): void
    {
        $testToken     = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId    = 'test-user-id';
        $testActorId   = 1;
        $testAdded     = gmdate('c');

        $now    = new DateTimeImmutable();
        $expiry = (string) $now->add(new DateInterval('P1Y'))->getTimestamp();

        $this->dynamoDbClientProphecy->updateItem(Argument::that(function (array $data) use ($testToken, $expiry) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            $this->assertArrayHasKey('Key', $data);
            $this->assertEquals(['Id' => ['S' => $testToken]], $data['Key']);

            $this->assertArrayHasKey('UpdateExpression', $data);
            $this->assertEquals('set ActivateBy = :a, DueBy = :b', $data['UpdateExpression']);

            $this->assertArrayHasKey(':a', $data['ExpressionAttributeValues']);
            $this->assertArrayHasKey('N', $data['ExpressionAttributeValues'][':a']);
            $this->assertEqualsWithDelta($expiry, $data['ExpressionAttributeValues'][':a']['N'], 5);

            $this->assertArrayHasKey(':b', $data['ExpressionAttributeValues']);
            $this->assertArrayHasKey('S', $data['ExpressionAttributeValues'][':b']);

            $this->assertArrayNotHasKey(':c', $data['ExpressionAttributeValues']);

            return true;
        }))->willReturn($this->createAWSResult(
            [
                'Item' => [
                    'Id'         => [
                        'S' => $testToken,
                    ],
                    'SiriusUid'  => [
                        'S' => $testSiriusUid,
                    ],
                    'Added'      => [
                        'S' => $testAdded,
                    ],
                    'ActorId'    => [
                        'S' => (string)$testActorId,
                    ],
                    'UserId'     => [
                        'S' => $testUserId,
                    ],
                    'ActivateBy' => [
                        'N' => $expiry,
                    ],
                    'DueBy'      => [
                        'S' => 'P2W',
                    ],
                ],
            ]
        ));

        $userLpaActorMapRepo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME,
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $renew = $userLpaActorMapRepo->updateRecord($testToken, new DateInterval('P1Y'), new DateInterval('P2W'), null);
        $this->assertEquals($testToken, $renew['Id']);
    }
}
