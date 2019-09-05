<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorLpaCodes;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use DateTime;

class ActorLpaCodesTest extends TestCase
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
        $testCode = '123456789012';

        $this->dynamoDbClientProphecy->getItem(Argument::that(function(array $data) use ($testCode) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('ActorLpaCode', $data['Key']);

                $this->assertEquals(['S' => $testCode], $data['Key']['ActorLpaCode']);

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'ActorLpaCode' => [
                        'S' => $testCode,
                    ],
                ],
            ]));

        $repo = new ActorLpaCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $repo->get($testCode);

        $this->assertEquals($testCode, $result['ActorLpaCode']);
    }

    public function testGetNotFound()
    {
        $testCode = '123456789012';

        $this->dynamoDbClientProphecy->getItem(Argument::that(function(array $data) use ($testCode) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('ActorLpaCode', $data['Key']);

                $this->assertEquals(['S' => $testCode], $data['Key']['ActorLpaCode']);

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => []
            ]));

        $repo = new ActorLpaCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Code not found');

        $repo->get($testCode);
    }
}
