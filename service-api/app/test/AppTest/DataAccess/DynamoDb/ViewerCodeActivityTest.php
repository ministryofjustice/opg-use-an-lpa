<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ViewerCodeActivity;
use App\DataAccess\DynamoDb\ViewerCodeActivityFactory;
use Aws\DynamoDb\DynamoDbClient;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Prophecy\Argument;

class ViewerCodeActivityTest extends TestCase
{
    const TABLE_NAME = 'test-table-name';

    private $dynamoDbClient;

    protected function setUp()
    {
        $this->dynamoDbClient = $this->prophesize(DynamoDbClient::class);
    }

    public function testRecordSuccessfulLookupActivity()
    {
        $testCode = '123456789ABC';

        $this->dynamoDbClient->putItem(Argument::that(function ($v) use ($testCode) {
            $this->assertArrayHasKey('TableName', $v);
            $this->assertEquals(self::TABLE_NAME, $v['TableName']);

            //---

            $this->assertArrayHasKey('Item', $v);
            $this->assertArrayHasKey('ViewerCode', $v['Item']);

            $this->assertEquals(['S' => $testCode], $v['Item']['ViewerCode']);

            $this->assertArrayHasKey('Viewed', $v['Item']);
            $this->assertArrayHasKey('S', $v['Item']['Viewed']);
            $time = new DateTime($v['Item']['Viewed']['S']);

            // We test the timestamp is now, with a 2 second allowance
            $this->assertEqualsWithDelta(time(), $time->getTimestamp(), 2);

            return true;
        }))->shouldBeCalled();

        //---

        $repo = new ViewerCodeActivity(
            $this->dynamoDbClient->reveal(),
            self::TABLE_NAME
        );

        $repo->recordSuccessfulLookupActivity($testCode);
    }

}
