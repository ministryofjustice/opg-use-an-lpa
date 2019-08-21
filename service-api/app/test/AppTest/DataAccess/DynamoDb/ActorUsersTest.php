<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorUsers;
use Aws\DynamoDb\DynamoDbClient;
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
}
