<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Entity\User;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class UserTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_can_be_constructed(): void
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertInstanceOf(User::class, $user);
    }

    /** @test */
    public function it_returns_valid_user_details(): void
    {
        $date = new DateTimeImmutable();
        $user = new User('test', [], [
            'Email'      => 'a@b.com',
            'LastLogin'  => $date->format(DateTimeInterface::ATOM),
            'NeedsReset' => strtotime('now'),
            'Subject'    => 'fakeSubId',
            'IdToken'    => 'fakeToken',
        ]);

        $this->assertIsArray($user->getDetails());
        $this->assertCount(5, $user->getDetails());
        $this->assertArrayHasKey('LastLogin', $user->getDetails());
        $this->assertArrayHasKey('NeedsReset', $user->getDetails());
        $this->assertArrayHasKey('Subject', $user->getDetails());
        $this->assertArrayHasKey('IdToken', $user->getDetails());
    }

    /** @test */
    public function needs_reset_is_false_when_not_supplied(): void
    {
        $date = new DateTimeImmutable();
        $user = new User('test', [], [
            'Email'     => 'a@b.com',
            'LastLogin' => $date->format(DateTimeInterface::ATOM),
        ]);

        $this->assertIsArray($user->getDetails());
        $this->assertCount(3, $user->getDetails());
        $this->assertFalse($user->getDetail('NeedsReset'));
    }

    /** @test */
    public function subject_and_idtoken_are_absent_when_not_supplied(): void
    {
        $date = new DateTimeImmutable();
        $user = new User('test', [], [
            'Email'     => 'a@b.com',
            'LastLogin' => $date->format(DateTimeInterface::ATOM),
        ]);

        $this->assertIsArray($user->getDetails());
        $this->assertCount(3, $user->getDetails());
        $this->assertArrayNotHasKey('Subject', $user->getDetails());
        $this->assertArrayNotHasKey('IdToken', $user->getDetails());
    }

    /** @test */
    public function it_returns_expected_roles(): void
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertIsArray($user->getRoles());
        $this->assertCount(0, $user->getRoles());
    }

    /** @test */
    public function it_returns_a_valid_lastLogin_time(): void
    {
        // this is needed to ensure our datetime is truncated at 0 microseconds
        // which is what we store and pass it around as everywhere but this test
        $date = new DateTimeImmutable();
        $date = $date->format(DateTimeInterface::ATOM);
        $date = new DateTimeImmutable($date);

        $user = new User('test', [], [
            'Email'     => 'a@b.com',
            'LastLogin' => $date->format(DateTimeInterface::ATOM),
        ]);

        $this->assertEquals($date, $user->getDetail('LastLogin'));
    }

    /** @test */
    public function it_returns_a_valid_lastLogin_time_when_constructed_from_DateTime_array(): void
    {
        $date = new DateTimeImmutable();

        $user = new User(
            '
            test',
            [],
            [
                'LastLogin' => [
                    'date'          => $date->format('Y-m-d H:i:s.u'),
                    'timezone_type' => 3,
                    'timezone'      => $date->getTimezone()->getName(),
                ],
                'Email'     => 'a@b.com',
            ],
        );

        $this->assertEquals($date, $user->getDetail('LastLogin'));
    }

    /** @test */
    public function it_returns_a_default_detail_when_provided_and_necessary(): void
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertEquals('Never', $user->getDetail('LastLogin', 'Never'));
    }

    /** @test */
    public function it_returns_a_null_when_property_not_found(): void
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertNull($user->getDetail('TestProperty'));
    }

    /** @test */
    public function it_returns_a_null_lastLogin_when_none_provided(): void
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertNull($user->getDetail('LastLogin'));
    }

    /** @test */
    public function it_returns_the_correct_identity(): void
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertEquals('test', $user->getIdentity());
    }

    /** @test */
    public function it_can_be_serialised(): void
    {
        $date = new DateTimeImmutable();
        $user = new User('test', [], [
            'Email'      => 'a@b.com',
            'LastLogin'  => $date->format(DateTimeInterface::ATOM),
            'NeedsReset' => strtotime('now'),
            'Subject'    => 'fakeSubId',
            'IdToken'    => 'fakeToken',
        ]);

        $encodedUser = json_encode($user);

        // should be a valid json structure (raw string checks)
        $this->assertJson($encodedUser);
        $this->assertStringContainsString('a@b.com', $encodedUser);
        $this->assertStringContainsString('Subject', $encodedUser);
        $this->assertStringContainsString('IdToken', $encodedUser);

        $userData = json_decode($encodedUser);
        $this->assertEqualsWithDelta(
            $date->getTimestamp(),
            (new DateTimeImmutable($userData->LastLogin))->getTimestamp(),
            3
        );
    }
}
