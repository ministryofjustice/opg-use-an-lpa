<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ViewerCodes;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use DateTime;

class ViewerCodesTest extends TestCase
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

        $expectedData = [
            'ViewerCode' => $testCode,
            'SiriusId'   => '123456789012',
            'Expires'    => new DateTime('2019-01-01 12:34:56'),
        ];

        $this->dynamoDbClientProphecy->getItem(Argument::that(function(array $data) use ($testCode) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('ViewerCode', $data['Key']);

                $this->assertEquals(['S' => $testCode], $data['Key']['ViewerCode']);

                return true;
            }))
            ->willReturn($this->generateAwsResult($expectedData));

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $data = $repo->get($testCode);

        $this->assertEquals($expectedData, $data);
    }

    public function testGetNotFound()
    {
        $testCode = '123456789012';

        $this->dynamoDbClientProphecy->getItem(Argument::that(function(array $data) use ($testCode) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('ViewerCode', $data['Key']);

                $this->assertEquals(['S' => $testCode], $data['Key']['ViewerCode']);

                return true;
            }))
            ->willReturn($this->generateAwsResult([]));

        $repo = new ViewerCodes($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Code not found');

        $repo->get($testCode);
    }
}
