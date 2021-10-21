<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\UserLpaActorMap;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class UserLpaActorMapTest extends TestCase
{
    use GenerateAwsResultTrait;

    public const TABLE_NAME = 'test-table-name';

    /** @var ObjectProphecy|DynamoDbClient */
    private $dynamoDbClientProphecy;

    public function assertIsValidUuid($uuid, string $message = '')
    {
        $pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        self::assertRegExp($pattern, $uuid, $message);
    }

    protected function setUp()
    {
        $this->dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
    }

    //--------------------------------------------------------------------------------
    // Get

    /** @test */
    public function can_lookup_a_token()
    {
        $testToken = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId = 'test-user-id';
        $testActorId = 1;
        $testAdded = gmdate('c');

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
                    'Id' => [
                        'S' => $testToken,
                    ],
                    'SiriusUid' => [
                        'S' => $testSiriusUid,
                    ],
                    'UserId' => [
                        'S' => $testUserId,
                    ],
                    'ActorId' => [
                        'N' => $testActorId,
                    ],
                    'Added' => [
                        'S' => $testAdded,
                    ],
                ],
            ]))->shouldBeCalled();

        $repo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->get($testToken);

        $this->assertEquals($testToken, $result['Id']);
        $this->assertEquals($testSiriusUid, $result['SiriusUid']);
        $this->assertEquals($testUserId, $result['UserId']);
        $this->assertInstanceOf(DateTime::class, $result['Added']);
        $this->assertEquals($testAdded, $result['Added']->format('c'));
        $this->assertEquals($testActorId, $result['ActorId']);
    }

    /** @test */
    public function cannot_lookup_a_missing_code()
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
                'Item' => []
            ]))->shouldBeCalled();

        $repo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->get($testToken);

        // Null is returned on a Not Found

        $this->assertNull($result);
    }

    //--------------------------------------------------------------------------------
    // Create

    /** @test */
    public function add_unique_token()
    {
        $testSiriusUid = 'test-uid';
        $testUserId = 'test-user-id';
        $testActorId = 1;

        $this->dynamoDbClientProphecy->putItem(Argument::that(function (array $data) use (
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
            $this->assertArrayNotHasKey('ActivateBy', $data);

            $this->assertEquals('attribute_not_exists(Id)', $data['ConditionExpression']);

            $this->assertIsValidUuid($data['Item']['Id']['S']);
            $this->assertEquals(['S' => $testUserId], $data['Item']['UserId']);
            $this->assertIsString($data['Item']['UserId']['S']);
            $this->assertEquals(['S' => $testSiriusUid], $data['Item']['SiriusUid']);
            $this->assertIsString($data['Item']['SiriusUid']['S']);
            $this->assertEquals(['N' => $testActorId], $data['Item']['ActorId']);
            $this->assertIsString($data['Item']['ActorId']['N']);

            // Checks 'now' is correct, we a little bit of leeway
            $this->assertEqualsWithDelta(time(), strtotime($data['Item']['Added']['S']), 5);

            return true;
        }))->shouldBeCalled();

        $repo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $repo->create($testUserId, $testSiriusUid, (string)$testActorId);
    }

    /** @test */
    public function add_unique_token_with_TTL()
    {
        $yearInEpoch = 31536000;
        $testSiriusUid = 'test-uid';
        $testUserId = 'test-user-id';
        $testActorId = 1;

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

        $repo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $repo->create($testUserId, $testSiriusUid, (string)$testActorId, 'P365D', 'P2W');
    }

    /** @test */
    public function add_conflicting_code()
    {
        /** @var MockObject|DynamoDbClient $dDBMock */
        $dDBMock = $this->getMockBuilder(DynamoDbClient::class)
            ->setMethods(['putItem'])
            ->disableOriginalConstructor()
            ->getMock();

        $dDBMock
            ->expects($this->exactly(2))
            ->method('putItem')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                $this->throwException(
                    new DynamoDbException(
                        'exception',
                        $this->prophesize(\Aws\CommandInterface::class)->reveal(),
                        ['code' => 'ConditionalCheckFailedException']
                    )
                ),
                $this->returnValue('')
            );

        //---

        $repo = new UserLpaActorMap($dDBMock, self::TABLE_NAME);

        $id = $repo->create('test-val', 'test-val', 'test-val');

        $this->assertIsValidUuid($id);
    }

    /** @test */
    public function test_unknown_exception_when_adding_code()
    {
        $this->dynamoDbClientProphecy->putItem(Argument::any())
            ->willThrow(DynamoDbException::class)
            ->shouldBeCalled();

        //---

        $repo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        // We should now expect a DynamoDbException
        $this->expectException(DynamoDbException::class);

        $repo->create('test-val', 'test-val', 'test-val');
    }

    //--------------------------------------------------------------------------------
    // Delete

    /** @test */
    public function can_delete_map()
    {
        $testToken = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId = 'test-user-id';
        $testActorId = 1;
        $testAdded = gmdate('c');

        $this->dynamoDbClientProphecy->deleteItem(Argument::that(function (array $data) use ($testToken) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            $this->assertArrayHasKey('Key', $data);
            $this->assertEquals(['Id' => ['S' => $testToken]], $data['Key']);

            return true;
        }))->willReturn($this->createAWSResult([
            'Item' => [
                'Id' => [
                    'S' => $testToken,
                ],
                'SiriusUid' => [
                    'S' => $testSiriusUid,
                ],
                'Added' => [
                    'S' => $testAdded,
                ],
                'ActorId' => [
                    'S' => (string) $testActorId
                ],
                'UserId' => [
                    'S' => $testUserId
                ],
            ]
        ]));

        $userLpaActorMapRepo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $removeActorMap = $userLpaActorMapRepo->delete($testToken);

        $this->assertEquals($testToken, $removeActorMap['Id']);
    }

    //--------------------------------------------------------------------------------
    // Activate record

    /** @test */
    public function can_activate_record()
    {
        $testToken = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId = 'test-user-id';
        $testActorId = 1;
        $testAdded = gmdate('c');

        $this->dynamoDbClientProphecy->updateItem(Argument::that(function (array $data) use ($testToken) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            $this->assertArrayHasKey('Key', $data);
            $this->assertEquals(['Id' => ['S' => $testToken]], $data['Key']);

            $this->assertArrayHasKey('UpdateExpression', $data);
            $this->assertEquals('remove ActivateBy, DueBy', $data['UpdateExpression']);

            return true;
        }))->willReturn($this->createAWSResult([
            'Item' => [
                'Id' => [
                    'S' => $testToken,
                ],
                'SiriusUid' => [
                    'S' => $testSiriusUid,
                ],
                'Added' => [
                    'S' => $testAdded,
                ],
                'ActorId' => [
                    'S' => (string) $testActorId
                ],
                'UserId' => [
                    'S' => $testUserId
                ],
            ]
        ]));

        $userLpaActorMapRepo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $removeActorMap = $userLpaActorMapRepo->activateRecord($testToken);
        $this->assertEquals($testToken, $removeActorMap['Id']);
    }

    //--------------------------------------------------------------------------------
    // Get All

    /** @test */
    public function can_get_all_lpas_for_user()
    {
        $testToken = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId = 'test-user-id';
        $testActorId = 1;
        $testAdded = gmdate('c');

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
            $this->createAWSResult(['Items' => [
                [
                    'Id' => [
                        'S' => $testToken,
                    ],
                    'SiriusUid' => [
                        'S' => $testSiriusUid,
                    ],
                    'UserId' => [
                        'S' => $testUserId,
                    ],
                    'ActorId' => [
                        'N' => $testActorId,
                    ],
                    'Added' => [
                        'S' => $testAdded,
                    ],
                ]
            ]])
        )->shouldBeCalled();

        $repo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

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
    public function can_renew_activation_period()
    {
        $testToken = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId = 'test-user-id';
        $testActorId = 1;
        $testAdded = gmdate('c');

        $now = new DateTimeImmutable();
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

            return true;
        }))->willReturn($this->createAWSResult(
            [
                'Item' => [
                    'Id' => [
                        'S' => $testToken,
                    ],
                    'SiriusUid' => [
                        'S' => $testSiriusUid,
                    ],
                    'Added' => [
                        'S' => $testAdded,
                    ],
                    'ActorId' => [
                        'S' => (string)$testActorId,
                    ],
                    'UserId' => [
                        'S' => $testUserId,
                    ],
                    'ActivateBy' => [
                        'N' => $expiry,
                    ],
                    'DueBy' => [
                        'S' => 'P2W'
                    ]
                ],
            ]
        ));

        $userLpaActorMapRepo = new UserLpaActorMap(
            $this->dynamoDbClientProphecy->reveal(),
            self::TABLE_NAME
        );

        $renew = $userLpaActorMapRepo->updateRecord($testToken, 'P1Y', 'P2W', (string)$testActorId);
        $this->assertEquals($testToken, $renew['Id']);
    }
}
