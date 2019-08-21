<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorUsers;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ActorUsersTest extends TestCase
{
    /** @test */
    public function can_record_a_successful_login()
    {
        $date = (new DateTime('now'))->format(DateTimeInterface::ATOM);

        $dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
        $dynamoDbClientProphecy->updateItem(Argument::that(function(array $data) use ($date) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString('users-table', serialize($data));
                $this->assertStringContainsString('test@example.com', serialize($data));
                $this->assertStringContainsString($date, serialize($data));

                return true;
            }))
            ->shouldBeCalled();

        $actorRepo = new ActorUsers($dynamoDbClientProphecy->reveal(), 'users-table');

        $actorRepo->recordSuccessfulLogin('test@example.com', $date);
    }

    /** @test */
    public function can_record_a_password_reset_request()
    {
        $time = time() + (60 * 60 * 24);

        $dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
        $dynamoDbClientProphecy->updateItem(Argument::that(function(array $data) use ($time) {
            $this->assertIsArray($data);

            // we don't care what the array looks like as it's specific to the AWS api and may change
            // we do care that the data *at least* contains the items we want to affect
            $this->assertStringContainsString('users-table', serialize($data));
            $this->assertStringContainsString('test@example.com', serialize($data));
            $this->assertStringContainsString('resetTokenAABBCCDDEE', serialize($data));
            $this->assertStringContainsString((string) $time, serialize($data));

            return true;
        }))
            ->shouldBeCalled();

        $actorRepo = new ActorUsers($dynamoDbClientProphecy->reveal(), 'users-table');

        $actorRepo->recordPasswordResetRequest('test@example.com', 'resetTokenAABBCCDDEE', $time);
    }

    /** @test */
    public function can_reset_a_password_when_given_a_correct_reset_token()
    {
        $dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);

        $dynamoDbClientProphecy
            ->query(
                Argument::that(function(array $data) {
                    $this->assertIsArray($data);

                    return true;
                })
            )
            ->willReturn($this->createAWSResult([
                'Items' => [
                    [
                        'Email' => [
                            'S' => 'test@example.com',
                        ],
                        'PasswordResetToken' => [
                            'S' => 'resetTokenAABBCCDDEE',
                        ],
                    ]
                ]
            ]));

        $dynamoDbClientProphecy->updateItem(Argument::that(function(array $data) {
            $this->assertIsArray($data);

            // we don't care what the array looks like as it's specific to the AWS api and may change
            // we do care that the data *at least* contains the items we want to affect
            $this->assertStringContainsString('users-table', serialize($data));
            $this->assertStringContainsString('test@example.com', serialize($data));

            return true;
        }))
            ->shouldBeCalled();

        $actorRepo = new ActorUsers($dynamoDbClientProphecy->reveal(), 'users-table');

        $result = $actorRepo->resetPassword('resetTokenAABBCCDDEE', 'passwordHash');

        $this->assertTrue($result);
    }

    /** @test */
    public function cannot_reset_a_password_when_given_an_correct_reset_token()
    {
        $dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);

        $dynamoDbClientProphecy
            ->query(
                Argument::that(function(array $data) {
                    $this->assertIsArray($data);

                    return true;
                })
            )
            ->willReturn($this->createAWSResult([
                'Items' => []
            ]));

        $actorRepo = new ActorUsers($dynamoDbClientProphecy->reveal(), 'users-table');

        $result = $actorRepo->resetPassword('badResetTokenAABBCCDDEE', 'passwordHash');

        $this->assertFalse($result);
    }

    /**
     * Function to mock the AWS result so that calling a Dynamo instance isn't necessary
     *
     * General guidance is "Don't mock interfaces you don't control" but pending a big rewrite
     * of the DynamoDB layer this is going to have to do.
     *
     * Pushing an array into the function formatted how you'd expect an AWS response to
     * look gets you back something that behaves correctly for our current purposes.
     *
     * ```
     * $result = createAWSResult([
     *   'Items' => [
     *     [
     *       'Email' => [
     *         'S' => 'test@example.com',
     *       ],
     *       'PasswordResetToken' => [
     *         'S' => 'resetTokenAABBCCDDEE',
     *       ],
     *     ]
     *   ]
     * ])
     * ```
     */
    private function createAWSResult(array $items = []): Result
    {
        $awsResult = $this->createMock(Result::class);

        $iterator = new \ArrayIterator($items);

        $awsResult->expects($this->once())
            ->method('offsetExists')
            ->with($this->isType('string'))
            ->will($this->returnCallback(function($index) use ($iterator) {
                return $iterator->offsetExists($index);
            }));

        $awsResult->expects($this->once())
            ->method('offsetGet')
            ->with($this->isType('string'))
            ->will($this->returnCallback(function($index) use ($iterator) {
                return $iterator->offsetGet($index);
            }));

        return $awsResult;
    }
}
