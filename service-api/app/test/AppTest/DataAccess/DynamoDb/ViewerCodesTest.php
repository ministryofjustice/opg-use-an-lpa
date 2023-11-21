<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ViewerCodes;
use App\DataAccess\Repository\KeyCollisionException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Aws\CommandInterface;

class ViewerCodesTest extends TestCase
{
    use GenerateAwsResultTrait;
    use ProphecyTrait;

    const TABLE_NAME = 'test-table-name';

    private $dynamoDbClientProphecy;

    protected function setUp(): void
    {
        $this->dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
    }

    /** @test */
    public function can_lookup_a_code(): void
    {
        $testCode = 'test-code';

        $this->dynamoDbClientProphecy->getItem(
            Argument::that(function (array $data) use ($testCode) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('ViewerCode', $data['Key']);

                $this->assertEquals(['S' => $testCode], $data['Key']['ViewerCode']);

                return true;
            })
        )
            ->willReturn(
                $this->createAWSResult([
                                           'Item' => [
                                               'ViewerCode' => [
                                                   'S' => $testCode,
                                               ],
                                               'SiriusUid'  => [
                                                   'S' => '123456789012',
                                               ],
                                               'Expires'    => [
                                                   'S' => '2019-01-01 12:34:56',
                                               ],
                                           ],
                                       ])
            );

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->get($testCode);

        $this->assertEquals($testCode, $result['ViewerCode']);
        $this->assertEquals('123456789012', $result['SiriusUid']);
        $this->assertEquals(new DateTime('2019-01-01 12:34:56'), $result['Expires']);
    }

    /** @test */
    public function cannot_lookup_a_missing_code(): void
    {
        $testCode = 'test-code';

        $this->dynamoDbClientProphecy->getItem(
            Argument::that(function (array $data) use ($testCode) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('ViewerCode', $data['Key']);

                $this->assertEquals(['S' => $testCode], $data['Key']['ViewerCode']);

                return true;
            })
        )
            ->willReturn(
                $this->createAWSResult(
                    [
                        'Item' => [],
                    ]
                )
            );

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->get($testCode);

        // Null is returned on a Not Found
        $this->assertNull($result);
    }

    /** @test */
    public function can_query_by_lpa_id()
    {
        $testSiriusUid = '98765-43210';

        $this->dynamoDbClientProphecy->query(
            Argument::that(function (array $data) use ($testSiriusUid) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertArrayHasKey('IndexName', $data);
                $this->assertEquals('SiriusUidIndex', $data['IndexName']);

                $this->assertArrayHasKey('ExpressionAttributeValues', $data);
                $this->assertArrayHasKey(':uId', $data['ExpressionAttributeValues']);

                $this->assertEquals(['S' => $testSiriusUid], $data['ExpressionAttributeValues'][':uId']);

                return true;
            })
        )
            ->willReturn(
                $this->createAWSResult([
                                           'Items' => [
                                               [
                                                   'SiriusUid' => [
                                                       'S' => $testSiriusUid,
                                                   ],
                                               ],
                                           ],
                                           'Count' => 1,
                                       ])
            );

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->getCodesByLpaId($testSiriusUid);

        $this->assertEquals($testSiriusUid, $result[0]['SiriusUid']);
    }

    /** @test */
    public function lpa_with_no_generated_codes_returns_empty_array()
    {
        $testSiriusUid = '98765-43210';

        $this->dynamoDbClientProphecy->query(
            Argument::that(function (array $data) use ($testSiriusUid) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertArrayHasKey('IndexName', $data);
                $this->assertEquals('SiriusUidIndex', $data['IndexName']);

                $this->assertArrayHasKey('ExpressionAttributeValues', $data);
                $this->assertArrayHasKey(':uId', $data['ExpressionAttributeValues']);

                $this->assertEquals(['S' => $testSiriusUid], $data['ExpressionAttributeValues'][':uId']);

                return true;
            })
        )
            ->willReturn(
                $this->createAWSResult([
                                           'Items' => [],
                                           'Count' => 0,
                                       ])
            );

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->getCodesByLpaId($testSiriusUid);

        $this->assertEmpty($result);
    }

    /** @test */
    public function add_unique_code(): void
    {
        $testCode              = 'test-code';
        $testUserLpaActorToken = 'test-token';
        $testSiriusUid         = 'test-uid';
        $testExpires           = new DateTime();
        $testOrganisation      = 'test-organisation';
        $testActorId           = 123;

        $this->dynamoDbClientProphecy->putItem(
            Argument::that(function (array $data) use (
                $testCode,
                $testUserLpaActorToken,
                $testSiriusUid,
                $testExpires,
                $testOrganisation
            ) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('TableName', $data);
                $this->assertArrayHasKey('Item', $data);
                $this->assertArrayHasKey('ConditionExpression', $data);

                $this->assertEquals('attribute_not_exists(ViewerCode)', $data['ConditionExpression']);

                $this->assertEquals(['S' => $testCode], $data['Item']['ViewerCode']);
                $this->assertEquals(['S' => $testUserLpaActorToken], $data['Item']['UserLpaActor']);
                $this->assertEquals(['S' => $testSiriusUid], $data['Item']['SiriusUid']);
                $this->assertEquals(['S' => $testExpires->format('c')], $data['Item']['Expires']);
                $this->assertEquals(['S' => $testOrganisation], $data['Item']['Organisation']);

                // Checks 'now' is correct, we a little bit of leeway
                $this->assertEqualsWithDelta(time(), strtotime($data['Item']['Added']['S']), 5);

                return true;
            })
        )->shouldBeCalled();

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $repo->add($testCode, $testUserLpaActorToken, $testSiriusUid, $testExpires, $testOrganisation, $testActorId);
    }

    /** @test */
    public function add_conflicting_code(): void
    {
        $this->dynamoDbClientProphecy->putItem(Argument::any())
            ->willThrow(
                new DynamoDbException(
                    'exception',
                    $this->prophesize(CommandInterface::class)->reveal(),
                    ['code' => 'ConditionalCheckFailedException']
                )
            )
            ->shouldBeCalled();

        //---

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        // We expect our own KeyCollisionException
        $this->expectException(KeyCollisionException::class);

        $repo->add(
            'test-val',
            'test-val',
            'test-val',
            new DateTime(),
            'test-val',
            123
        );
    }

    /** @test */
    public function test_unknown_exception_when_adding_code(): void
    {
        $this->dynamoDbClientProphecy->putItem(Argument::any())
            ->willThrow(DynamoDbException::class)
            ->shouldBeCalled();

        //---

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        // We should now expect a DynamoDbException
        $this->expectException(DynamoDbException::class);

        $repo->add(
            'test-val',
            'test-val',
            'test-val',
            new DateTime(),
            'test-val',
            123
        );
    }

    /** @test */
    public function can_cancel_viewer_code(): void
    {
        $testCode    = 'test-code';
        $currentDate = new DateTime('today');

        $this->dynamoDbClientProphecy->updateItem(
            Argument::that(function (array $data) use (
                $testCode,
                $currentDate
            ) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertEquals(['S' => $testCode], $data['Key']['ViewerCode']);

                $this->assertArrayHasKey('UpdateExpression', $data);
                $this->assertEquals('SET Cancelled=:c', $data['UpdateExpression']);

                $this->assertArrayHasKey(':c', $data['ExpressionAttributeValues']);
                $this->assertEquals(['S' => $currentDate->format('c')], $data['ExpressionAttributeValues'][':c']);

                return true;
            })
        )->shouldBeCalled();

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->cancel($testCode, $currentDate);

        $this->assertTrue($result);
    }

    /** @test */
    public function can_remove_actor_and_code_association(): void
    {
        $testCode  = 'test-code';
        $codeOwner = 23;

        $this->dynamoDbClientProphecy->updateItem(
            Argument::that(function (array $data) use (
                $testCode
            ) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertEquals(['S' => $testCode], $data['Key']['ViewerCode']);

                $this->assertArrayHasKey('UpdateExpression', $data);
                $this->assertEquals('SET UserLpaActor=:c, CreatedBy=:d', $data['UpdateExpression']);

                $this->assertArrayHasKey(':c', $data['ExpressionAttributeValues']);
                $this->assertEquals(['S' => ''], $data['ExpressionAttributeValues'][':c']);

                return true;
            })
        )->shouldBeCalled();

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->removeActorAssociation($testCode, $codeOwner);

        $this->assertTrue($result);
    }
}
