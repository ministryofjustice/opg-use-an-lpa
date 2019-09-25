<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorCodes;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use DateTime;

class ActorCodesTest extends TestCase
{
    use GenerateAwsResultTrait;

    const TABLE_NAME = 'test-table-name';

    private $dynamoDbClientProphecy;

    protected function setUp()
    {
        $this->dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
    }

    public function testGet()
    {
        $testCode = 'XYUPHWQRECHV';
        $testSiriusUid = '700000000138';
        $testActorId = 1;
        $testExpires = gmdate('c');
        $testActive = true;

        $this->dynamoDbClientProphecy->getItem(Argument::that(function(array $data) use ($testCode) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('ActorCode', $data['Key']);

                $this->assertEquals(['S' => $testCode], $data['Key']['ActorCode']);

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'ActorCode' => [
                        'S' => $testCode,
                    ],
                    'SiriusUid' => [
                        'S' => $testSiriusUid,
                    ],
                    'Active' => [
                        'BOOL' => $testActive,
                    ],
                    'Expires' => [
                        'S' => $testExpires,
                    ],
                    'ActorLpaId' => [
                        'N' => $testActorId,
                    ],
                ],
            ]));

        $repo = new ActorCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->get($testCode);

        $this->assertEquals($testCode, $result['ActorCode']);
        $this->assertEquals($testSiriusUid, $result['SiriusUid']);
        $this->assertEquals($testActive, $result['Active']);
        $this->assertInstanceOf(DateTime::class, $result['Expires']);
        $this->assertEquals($testExpires, $result['Expires']->format('c'));
        $this->assertEquals($testActorId, $result['ActorLpaId']);
    }

    public function testGetNotFound()
    {
        $testCode = 'XYUPHWQRECHV';

        $this->dynamoDbClientProphecy->getItem(Argument::that(function(array $data) use ($testCode) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('ActorCode', $data['Key']);

                $this->assertEquals(['S' => $testCode], $data['Key']['ActorCode']);

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => []
            ]));

        $repo = new ActorCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->get($testCode);

        // Null is returned on a Not Found

        $this->assertNull($result);
    }
}
