<?php

declare(strict_types=1);

namespace AppTest\DataAccess\DynamoDb;

use App\DataAccess\DynamoDb\ActorUsers;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ActorUsersTest extends TestCase
{
    use GenerateAwsResultTrait;

    const TABLE_NAME = 'test-table-name';

    private $dynamoDbClientProphecy;

    protected function setUp()
    {
        $this->dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
    }

    /** @test */
    public function will_add_a_new_user()
    {
        $id = '12345-1234-1234-1234-12345';
        $email = 'a@b.com';
        $password = 'P@55word';
        $activationToken = 'actok123';
        $activationTtl = time() + 3600;

        $this->dynamoDbClientProphecy
            ->putItem(Argument::that(function ($data) use ($id, $email, $password, $activationToken, $activationTtl) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertArrayHasKey('Item', $data);
                $this->assertArrayHasKey('Id', $data['Item']);
                $this->assertArrayHasKey('Email', $data['Item']);
                $this->assertArrayHasKey('Password', $data['Item']);
                $this->assertArrayHasKey('ActivationToken', $data['Item']);
                $this->assertArrayHasKey('ExpiresTTL', $data['Item']);

                $this->assertEquals(['S' => $id], $data['Item']['Id']);
                $this->assertEquals(['S' => $email], $data['Item']['Email']);
                //  Can't directly compare the password value so just verify it
                $this->assertTrue(password_verify($password, $data['Item']['Password']['S']));
                $this->assertEquals(['S' => $activationToken], $data['Item']['ActivationToken']);
                $this->assertEquals(['N' => $activationTtl], $data['Item']['ExpiresTTL']);

                return true;
            }))
            ->shouldBeCalled();

        $this->dynamoDbClientProphecy
            ->getItem(Argument::that(function(array $data) use ($id) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('Id', $data['Key']);

                $this->assertEquals(['S' => $id], $data['Key']['Id']);

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'Id' => [
                        'S' => $id,
                    ],
                    'Email' => [
                        'S' => $email,
                    ],
                    'Password' => [
                        'S' => $password,
                    ],
                    'ActivationToken' => [
                        'S' => $activationToken,
                    ],
                    'ExpiresTTL' => [
                        'N' => $activationTtl,
                    ],
                ],
            ]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $actorRepo->add($id, $email, $password, $activationToken, $activationTtl);

        $this->assertEquals($id, $result['Id']);
        $this->assertEquals($email, $result['Email']);
        $this->assertEquals($password, $result['Password']);
        $this->assertEquals($activationToken, $result['ActivationToken']);
        $this->assertEquals($activationTtl, $result['ExpiresTTL']);
    }

    /** @test */
    public function will_throw_exception_when_adding_a_new_user_that_doesnt_succeed()
    {
        $id = '12345-1234-1234-1234-12345';
        $email = 'a@b.com';
        $password = 'P@55word';
        $activationToken = 'actok123';
        $activationTtl = time() + 3600;

        $this->dynamoDbClientProphecy
            ->putItem(Argument::that(function ($data) use ($id, $email, $password, $activationToken, $activationTtl) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertArrayHasKey('Item', $data);
                $this->assertArrayHasKey('Id', $data['Item']);
                $this->assertArrayHasKey('Email', $data['Item']);
                $this->assertArrayHasKey('Password', $data['Item']);
                $this->assertArrayHasKey('ActivationToken', $data['Item']);
                $this->assertArrayHasKey('ExpiresTTL', $data['Item']);

                $this->assertEquals(['S' => $id], $data['Item']['Id']);
                $this->assertEquals(['S' => $email], $data['Item']['Email']);
                //  Can't directly compare the password value so just verify it
                $this->assertTrue(password_verify($password, $data['Item']['Password']['S']));
                $this->assertEquals(['S' => $activationToken], $data['Item']['ActivationToken']);
                $this->assertEquals(['N' => $activationTtl], $data['Item']['ExpiresTTL']);

                return true;
            }))
                ->shouldBeCalled();

        $this->dynamoDbClientProphecy
            ->getItem(Argument::that(function(array $data) use ($id) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('Id', $data['Key']);

                $this->assertEquals(['S' => $id], $data['Key']['Id']);

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => []
            ]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(CreationException::class);
        $this->expectExceptionMessage('Unable to retrieve newly created actor from database');

        $actorRepo->add($id, $email, $password, $activationToken, $activationTtl);
    }

    /** @test */
    public function will_get_a_user_record()
    {
        $id = '12345-1234-1234-1234-12345';

        $this->dynamoDbClientProphecy
            ->getItem(Argument::that(function(array $data) use ($id) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('Id', $data['Key']);

                $this->assertEquals(['S' => $id], $data['Key']['Id']);

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'Id' => [
                        'S' => $id,
                    ],
                ],
            ]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $actorRepo->get($id);

        $this->assertEquals($id, $result['Id']);
    }

    /** @test */
    public function will_get_a_user_record_by_email()
    {
        $email = 'a@b.com';
        $id = '12345-1234-1234-1234-12345';

        $this->dynamoDbClientProphecy
            ->query(Argument::that(function(array $data) use ($email) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);
                $this->assertArrayHasKey('IndexName', $data);
                $this->assertEquals('EmailIndex', $data['IndexName']);

                $this->assertArrayHasKey('ExpressionAttributeValues', $data);
                $this->assertArrayHasKey(':email', $data['ExpressionAttributeValues']);

                $this->assertEquals(['S' => $email], $data['ExpressionAttributeValues'][':email']);

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Items' => [
                    [
                        'Id' => [
                            'S' => $id,
                        ]
                    ],
                ],
            ]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $actorRepo->getByEmail($email);

        $this->assertEquals($id, $result['Id']);
    }

    /** @test */
    public function will_fail_to_get_a_user_record()
    {
        $id = '12345-1234-1234-1234-12345';

        $this->dynamoDbClientProphecy
            ->getItem(Argument::that(function(array $data) use ($id) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                $this->assertArrayHasKey('Key', $data);
                $this->assertArrayHasKey('Id', $data['Key']);

                $this->assertEquals(['S' => $id], $data['Key']['Id']);

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => []
            ]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $actorRepo->get($id);
    }

    /** @test */
    public function will_fail_to_get_a_user_record_by_email()
    {
        $email = 'c@d.com';

        $this->dynamoDbClientProphecy
            ->query(Argument::that(function(array $data) use ($email) {
                $this->assertArrayHasKey('TableName', $data);
                $this->assertEquals(self::TABLE_NAME, $data['TableName']);

                //---

                $this->assertArrayHasKey('IndexName', $data);
                $this->assertEquals('EmailIndex', $data['IndexName']);

                //---

                $this->assertArrayHasKey('ExpressionAttributeValues', $data);
                $this->assertArrayHasKey(':email', $data['ExpressionAttributeValues']);

                $this->assertEquals(['S' => $email], $data['ExpressionAttributeValues'][':email']);

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => []
            ]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $actorRepo->getByEmail($email);
    }

    /** @test */
    public function will_activate_a_user_account()
    {
        $id = '12345-1234-1234-1234-12345';
        $email = 'a@b.com';
        $activationToken = 'activateTok123';

        $expectedData = [
            'Id'              => $id,
            'Email'           => $email,
            'Password'        => 'H@shedP@55word',
            'ActivationToken' => $activationToken,
            'ExpiresTTL'      => (string) time(),
        ];

        $this->dynamoDbClientProphecy
            ->query(Argument::that(function(array $data) use ($activationToken) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString(self::TABLE_NAME, serialize($data));
                $this->assertStringContainsString($activationToken, serialize($data));

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Items' => [
                    [
                        'Id' => [
                            'S' => $id,
                        ],
                        'Email' => [
                            'S' => $email,
                        ],
                        'Password' => [
                            'S' => 'H@shedP@55word',
                        ],
                        'ActivationToken' => [
                            'S' => $activationToken,
                        ],
                        'ExpiresTTL' => [
                            'N' => time(),
                        ],
                    ]
                ]
            ]));

        $this->dynamoDbClientProphecy
            ->updateItem(Argument::that(function(array $data) use ($id) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString(self::TABLE_NAME, serialize($data));
                $this->assertStringContainsString($id, serialize($data));

                //---

                $this->assertArrayHasKey('UpdateExpression', $data);
                $this->assertEquals('remove ActivationToken', $data['UpdateExpression']);

                return true;
            }))
            ->shouldBeCalled();

        $this->dynamoDbClientProphecy
            ->getItem(Argument::that(function(array $data) use ($id) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString(self::TABLE_NAME, serialize($data));
                $this->assertStringContainsString($id, serialize($data));

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Item' => [
                    'Id' => [
                        'S' => $id,
                    ],
                    'Email' => [
                        'S' => $email,
                    ],
                    'Password' => [
                        'S' => 'H@shedP@55word',
                    ],
                    'ActivationToken' => [
                        'S' => $activationToken,
                    ],
                    'ExpiresTTL' => [
                        'N' => time(),
                    ],
                ]
            ]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $result = $actorRepo->activate($activationToken);

        $this->assertEquals($email, $result['Email']);
        $this->assertEquals('H@shedP@55word', $result['Password']);
        $this->assertEquals($activationToken, $result['ActivationToken']);
        $this->assertEqualsWithDelta(time(), $result['ExpiresTTL'], 3);
    }

    /** @test */
    public function will_fail_to_activate_a_user_not_found()
    {
        $activationToken = 'activateTok123';

        $this->dynamoDbClientProphecy
            ->query(Argument::that(function(array $data) use ($activationToken) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString(self::TABLE_NAME, serialize($data));
                $this->assertStringContainsString($activationToken, serialize($data));

                    return true;
            }))
            ->willReturn($this->createAWSResult([
                'Items' => [],
            ]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found for token');

        $actorRepo->activate($activationToken);
    }

    /** @test */
    public function will_find_a_user_exists()
    {
        $id = '12345-1234-1234-1234-12345';
        $email = 'a@b.com';

        $this->dynamoDbClientProphecy
            ->query(Argument::that(function(array $data) use ($email) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString(self::TABLE_NAME, serialize($data));
                $this->assertStringContainsString($email, serialize($data));

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Items' => [
                    [
                        'Id' => [
                            'S' => $id,
                        ]
                    ],
                ],
            ]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->assertTrue($actorRepo->exists($email));
    }

    /** @test */
    public function will_not_find_a_user()
    {
        $email = 'c@d.com';

        $this->dynamoDbClientProphecy
            ->query(Argument::that(function(array $data) use ($email) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString(self::TABLE_NAME, serialize($data));
                $this->assertStringContainsString($email, serialize($data));

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Items' => []
            ]));

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $this->assertFalse($actorRepo->exists($email));
    }

    /** @test */
    public function will_record_a_successful_login()
    {
        $date = (new DateTime('now'))->format(DateTimeInterface::ATOM);

        $this->dynamoDbClientProphecy
            ->updateItem(Argument::that(function(array $data) use ($date) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString(self::TABLE_NAME, serialize($data));
                $this->assertStringContainsString('test@example.com', serialize($data));
                $this->assertStringContainsString($date, serialize($data));

                return true;
            }))
            ->shouldBeCalled();

        $actorRepo = new ActorUsers($this->dynamoDbClientProphecy->reveal(), self::TABLE_NAME);

        $actorRepo->recordSuccessfulLogin('test@example.com', $date);
    }

    /** @test */
    public function will_record_a_password_reset_request()
    {
        $id = '12345-1234-1234-1234-12345';
        $email = 'a@b.com';

        $dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
        $dynamoDbClientProphecy
            ->query(Argument::that(function(array $data) use ($email) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString('users-table', serialize($data));
                $this->assertStringContainsString($email, serialize($data));

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Items' => [
                    [
                        'Id' => [
                            'S' => $id,
                        ]
                    ]
                ]
            ]));

        $time = time() + (60 * 60 * 24);

        $dynamoDbClientProphecy
            ->updateItem(Argument::that(function(array $data) use ($id, $time) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString('users-table', serialize($data));
                $this->assertStringContainsString($id, serialize($data));
                $this->assertStringContainsString('resetTokenAABBCCDDEE', serialize($data));
                $this->assertStringContainsString((string) $time, serialize($data));

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Attributes' => [
                    'Id' => [
                        'S' => $id,
                    ],
                    'Email' => [
                        'S' => $email,
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

        $result = $actorRepo->recordPasswordResetRequest($email, 'resetTokenAABBCCDDEE', $time);

        $this->assertIsArray($result);
        $this->assertEquals($id, $result['Id']);
        $this->assertEquals($email, $result['Email']);
        $this->assertEquals('resetTokenAABBCCDDEE', $result['PasswordResetToken']);
        $this->assertEquals('12345678912', $result['PasswordResetExpiry']);
    }

    /** @test */
    public function will_fail_to_record_a_password_reset_request_if_user_does_not_exist()
    {
        $email = 'a@b.com';

        $dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
        $dynamoDbClientProphecy
            ->query(Argument::that(function(array $data) use ($email) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString('users-table', serialize($data));
                $this->assertStringContainsString($email, serialize($data));

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Items' => []
            ]));

        $actorRepo = new ActorUsers($dynamoDbClientProphecy->reveal(), 'users-table');

        $time = time() + (60 * 60 * 24);

        $this->expectException(NotFoundException::class);
        $result = $actorRepo->recordPasswordResetRequest($email, 'resetTokenAABBCCDDEE', $time);
    }

    /** @test */
    public function will_reset_a_password_when_given_a_correct_reset_token()
    {
        $id = '12345-1234-1234-1234-12345';
        $resetToken = 'resetTokenAABBCCDDEE';

        $dynamoDbClientProphecy = $this->prophesize(DynamoDbClient::class);
        $dynamoDbClientProphecy
            ->query(Argument::that(function(array $data) use ($resetToken) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString('users-table', serialize($data));
                $this->assertStringContainsString($resetToken, serialize($data));

                return true;
            }))
            ->willReturn($this->createAWSResult([
                'Items' => [
                    [
                        'Id' => [
                            'S' => $id,
                        ]
                    ]
                ]
            ]));

        $dynamoDbClientProphecy
            ->updateItem(Argument::that(function(array $data) use ($id) {
                $this->assertIsArray($data);

                // we don't care what the array looks like as it's specific to the AWS api and may change
                // we do care that the data *at least* contains the items we want to affect
                $this->assertStringContainsString('users-table', serialize($data));
                $this->assertStringContainsString($id, serialize($data));

                return true;
            }))
            ->shouldBeCalled();

        $actorRepo = new ActorUsers($dynamoDbClientProphecy->reveal(), 'users-table');

        $result = $actorRepo->resetPassword($resetToken, 'password');

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
}
