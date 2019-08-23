<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorUsers;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ActorUsersTest extends TestCase
{
    /** @test */
    public function will_record_a_successful_login()
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
    public function will_record_a_password_reset_request()
    {
        $dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
        $dynamoDbClientProphecy
            ->getItem(Argument::that(function(array $data) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString('users-table', serialize($data));
                $this->assertStringContainsString('test@example.com', serialize($data));

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'Email' => [
                        'S' => 'test@example.com',
                    ],
                ]
            ]));

        $time = time() + (60 * 60 * 24);

        $dynamoDbClientProphecy
            ->updateItem(Argument::that(function(array $data) use ($time) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString('users-table', serialize($data));
                $this->assertStringContainsString('test@example.com', serialize($data));
                $this->assertStringContainsString('resetTokenAABBCCDDEE', serialize($data));
                $this->assertStringContainsString((string) $time, serialize($data));

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Attributes' => [
                    'Email' => [
                        'S' => 'test@example.com',
                    ],
                    'PasswordResetToken' => [
                        'S' => 'resetTokenAABBCCDDEE',
                    ],
                    'PasswordResetExpiry' => [
                        'N' => '12345678912',
                    ],
                ]
            ]));

        $actorRepo = new ActorUsers($dynamoDbClientProphecy->reveal(), 'users-table');

        $result = $actorRepo->recordPasswordResetRequest('test@example.com', 'resetTokenAABBCCDDEE', $time);

        $this->assertIsArray($result);
        $this->assertEquals('test@example.com', $result['Email']);
        $this->assertEquals('resetTokenAABBCCDDEE', $result['PasswordResetToken']);
        $this->assertEquals('12345678912', $result['PasswordResetExpiry']);
    }

    /** @test */
    public function will_fail_to_record_a_password_reset_request_if_user_does_not_exist()
    {
        $dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
        $dynamoDbClientProphecy
            ->getItem(Argument::that(function(array $data) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString('users-table', serialize($data));
                $this->assertStringContainsString('test@example.com', serialize($data));

                return true;
            }))
            ->willThrow(new NotFoundException("User not found"));

        $actorRepo = new ActorUsers($dynamoDbClientProphecy->reveal(), 'users-table');

        $time = time() + (60 * 60 * 24);

        $this->expectException(NotFoundException::class);
        $result = $actorRepo->recordPasswordResetRequest('test@example.com', 'resetTokenAABBCCDDEE', $time);
    }

    /** @test */
    public function will_reset_a_password_when_given_a_correct_reset_token()
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
    public function will_not_reset_a_password_when_given_an_correct_reset_token()
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
     * ```php
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
        // wrap our array in a basic iterator
        $iterator = new \ArrayIterator($items);

        // using PHPUnit's mock as opposed to Prophecy since Prophecy doesn't support
        // "return by reference" which is what `foreach` expects.
        $awsResult = $this->createMock(Result::class);

        $awsResult
            ->method('offsetExists')
            ->with($this->isType('string'))
            ->will($this->returnCallback(function($index) use ($iterator) {
                return $iterator->offsetExists($index);
            }));

        $awsResult
            ->method('offsetGet')
            ->with($this->isType('string'))
            ->will($this->returnCallback(function($index) use ($iterator) {
                return $iterator->offsetGet($index);
            }));

        return $awsResult;
    }
}
