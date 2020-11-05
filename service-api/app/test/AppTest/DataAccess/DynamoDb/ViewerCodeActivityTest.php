<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ViewerCodeActivity;
use Aws\DynamoDb\DynamoDbClient;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ViewerCodeActivityTest extends TestCase
{
    use GenerateAwsResultTrait;

    const TABLE_NAME = 'test-table-name';

    private $dynamoDbClient;

    protected function setUp()
    {
        $this->dynamoDbClient = $this->prophesize(DynamoDbClient::class);
    }

    public function testRecordSuccessfulLookupActivity()
    {
        $testCode = '123456789ABC';
        $organisation = 'HSBC';

        $this->dynamoDbClient->putItem(Argument::that(function ($v) use ($testCode, $organisation) {
            $this->assertArrayHasKey('TableName', $v);
            $this->assertEquals(self::TABLE_NAME, $v['TableName']);

            //---

            $this->assertArrayHasKey('Item', $v);
            $this->assertArrayHasKey('ViewerCode', $v['Item']);

            $this->assertEquals(['S' => $testCode], $v['Item']['ViewerCode']);

            $this->assertArrayHasKey('Viewed', $v['Item']);
            $this->assertArrayHasKey('S', $v['Item']['Viewed']);

            $this->assertArrayHasKey('ViewedBy', $v['Item']);
            $this->assertArrayHasKey('S', $v['Item']['ViewedBy']);
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

        $repo->recordSuccessfulLookupActivity($testCode, $organisation);
    }

    /** @test */
    public function viewerCodesStatusSetToTrueWithQueryMatch()
    {
        $testCodes = [
            0 => [
                'ViewerCode' => 'RT6Y98VEF7A2'
                ]
        ];

        $this->dynamoDbClient->query(Argument::that(function (array $data) use ($testCodes) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            $this->assertArrayHasKey('KeyConditionExpression', $data);
            $this->assertArrayHasKey('ExpressionAttributeValues', $data);
            $this->assertArrayHasKey(':code', $data['ExpressionAttributeValues']);

            $this->assertEquals(['S' => $testCodes[0]['ViewerCode']], $data['ExpressionAttributeValues'][':code']);

            return true;
        }))
            ->willReturn($this->createAWSResult([
                'Items' => [
                    [
                        'Viewed' => [
                            'S' => '2020-01-11'
                        ],
                        'ViewerCode' => [
                            'S' => $testCodes[0]['ViewerCode']
                        ],
                        'ViewedBy' => [
                            'S' => 'Some organisation1'
                        ],
                    ],
                    [
                        'Viewed' => [
                            'S' => '2020-01-11'
                        ],
                        'ViewerCode' => [
                            'S' => $testCodes[0]['ViewerCode']
                        ],
                        'ViewedBy' => [
                            'S' => 'Some organisation2'
                        ],
                    ],
                ],
                'Count' => 1
            ]));

        $repo = new ViewerCodeActivity($this->dynamoDbClient->reveal(), self::TABLE_NAME);

        $result = $repo->getStatusesForViewerCodes($testCodes);

        $viewerCode = $testCodes[0]['ViewerCode'];

        foreach ($result[0]['Viewed'] as $viewedInstance => $viewedData) {
            $this->assertEquals($testCodes[0]['ViewerCode'], $viewedData['ViewerCode']);
            $this->assertNotEmpty($viewedData['Viewed']);
        }
        $this->assertEquals('Some organisation1', $result[0]['Viewed'][0]['ViewedBy']);
        $this->assertEquals('Some organisation2', $result[0]['Viewed'][1]['ViewedBy']);

//        $this->assertEquals($viewerCode, $result[0]['ViewerCode']);
//        $this->assertNotEmpty($result[0]['Viewed'][0]);
//        $this->assertEquals($viewerCode, $result[0]['Viewed'][0]['ViewerCode']);
//        $this->assertNotEmpty($result[0]['Viewed'][0]['Viewed']);
//        $this->assertEquals($viewerCode, $result[0]['Viewed'][1]['ViewerCode']);
//        $this->assertNotEmpty($result[0]['Viewed'][1]['Viewed']);
    }

    /** @test */
    public function viewerCodesStatusSetToFalseWithNoQueryMatch()
    {

        $testCodes = [['ViewerCode' => 'RT6Y98VEF7A2']];

        $this->dynamoDbClient->query(Argument::that(function (array $data) use ($testCodes) {
            $this->assertArrayHasKey('TableName', $data);
            $this->assertEquals(self::TABLE_NAME, $data['TableName']);

            $this->assertArrayHasKey('KeyConditionExpression', $data);
            $this->assertArrayHasKey('ExpressionAttributeValues', $data);
            $this->assertArrayHasKey(':code', $data['ExpressionAttributeValues']);

            $this->assertEquals(['S' => $testCodes[0]['ViewerCode']], $data['ExpressionAttributeValues'][':code']);

            return true;
        }))
            ->willReturn($this->createAWSResult([
                'Items' => [],
                'Count' => 0
            ]));

        $repo = new ViewerCodeActivity($this->dynamoDbClient->reveal(), self::TABLE_NAME);

        $result = $repo->getStatusesForViewerCodes($testCodes);

        $this->assertEquals($testCodes[0]['ViewerCode'], $result[0]['ViewerCode']);
        $this->assertEquals(false, $result[0]['Viewed']);

    }
}
