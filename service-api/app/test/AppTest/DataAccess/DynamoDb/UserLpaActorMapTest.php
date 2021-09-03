<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\Repository\KeyCollisionException;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use DateTime;

class UserLpaActorMapTest extends TestCase
{
    use GenerateAwsResultTrait;

    public const TABLE_NAME = 'test-table-name';

    private $dynamoDbClientProphecy;

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
        $testToken = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId = 'test-user-id';
        $testActorId = 1;

        $this->dynamoDbClientProphecy->putItem(Argument::that(function (array $data) use (
            $testToken,
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

            $this->assertEquals(['S' => $testToken], $data['Item']['Id']);
            $this->assertIsString($data['Item']['Id']['S']);
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

        $repo->create($testToken, $testUserId, $testSiriusUid, (string)$testActorId);
    }

    /** @test */
    public function add_unique_token_with_TTL()
    {
        $yearInEpoch = 31536000;
        $testToken = 'test-token';
        $testSiriusUid = 'test-uid';
        $testUserId = 'test-user-id';
        $testActorId = 1;

        $this->dynamoDbClientProphecy->putItem(Argument::that(function (array $data) use (
            $yearInEpoch,
            $testToken,
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

            $this->assertEquals(['S' => $testToken], $data['Item']['Id']);
            $this->assertIsString($data['Item']['Id']['S']);
            $this->assertEquals(['S' => $testUserId], $data['Item']['UserId']);
            $this->assertIsString($data['Item']['UserId']['S']);
            $this->assertEquals(['S' => $testSiriusUid], $data['Item']['SiriusUid']);
            $this->assertIsString($data['Item']['SiriusUid']['S']);
            $this->assertEquals(['N' => $testActorId], $data['Item']['ActorId']);
            $this->assertIsString($data['Item']['ActorId']['N']);

            // Checks 'now' is correct, we a little bit of leeway
            $this->assertEqualsWithDelta(time(), strtotime($data['Item']['Added']['S']), 5);

            $this->assertEqualsWithDelta(time() + $yearInEpoch, $data['Item']['ActivateBy']['N'], 5);

            return true;
        }))->shouldBeCalled();

        $repo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $repo->create($testToken, $testUserId, $testSiriusUid, (string)$testActorId, 'P365D');
    }

    /** @test */
    public function add_conflicting_code()
    {
        $this->dynamoDbClientProphecy->putItem(Argument::any())
            ->willThrow(new DynamoDbException(
                'exception',
                $this->prophesize(\Aws\CommandInterface::class)->reveal(),
                ['code' => 'ConditionalCheckFailedException']
            ))
            ->shouldBeCalled();

        //---

        $repo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        // We expect our own KeyCollisionException
        $this->expectException(KeyCollisionException::class);

        $repo->create('test-val', 'test-val', 'test-val', 'test-val');
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

        $repo->create('test-val', 'test-val', 'test-val', 'test-val');
    }

    //--------------------------------------------------------------------------------
    // Delete

    /** @test */
    public function can_delete_map()
    {
        $testToken = 'test-token';

        $this->dynamoDbClientProphecy->deleteItem(Argument::that(function (array $data) use ($testToken) {
            $this->assertIsArray($data);

            $this->assertStringContainsString(self::TABLE_NAME, serialize($data));
            $this->assertStringContainsString($testToken, serialize($data));

            return true;
        }))->willReturn($this->createAWSResult([
            'Item' => [
                'Id' => [
                    'S' => $id,
                ],
                'SiriusUid' => [
                    'S' => $siriusUid,
                ],
                'Added' => [
                    'S' => $added,
                ],
                'ActorId' => [
                    'S' => $actorId
                ],
                'UserId' => [
                    'S' => $userId
                ],
            ]
        ]));

        $userLpaActorMaprepo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $removeActorMap = $userLpaActorMaprepo->delete($testToken);
        $this->assertEquals($id, $removeActorMap['Id']);
    }

    //Remove ActivateBy

    /** @test */
    public function can_remove_activate_by()
    {
        $testToken = 'test-token';

        $this->dynamoDbClientProphecy->updateItem(Argument::that(function (array $data) use ($testToken) {
            $this->assertIsArray($data);

            $this->assertStringContainsString(self::TABLE_NAME, serialize($data));
            $this->assertStringContainsString($testToken, serialize($data));

            return true;
        }))->willReturn($this->createAWSResult([
           'Item' => [
               'Id' => [
                   'S' => $id,
               ],
               'SiriusUid' => [
                   'S' => $siriusUid,
               ],
               'Added' => [
                   'S' => $added,
               ],
               'ActorId' => [
                   'S' => $actorId
               ],
               'UserId' => [
                   'S' => $userId
               ],
           ]
        ]));

        $userLpaActorMaprepo = new UserLpaActorMap($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $removeActorMap = $userLpaActorMaprepo->removeActivateBy($testToken);
        $this->assertEquals($id, $removeActorMap['Id']);
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

        $result = $repo->getUsersLpas($testUserId);

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
}
