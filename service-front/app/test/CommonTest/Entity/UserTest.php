<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Entity\User;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /** @test */
    public function it_can_be_constructed()
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertInstanceOf(User::class, $user);
    }

    /** @test */
    public function it_returns_valid_user_details()
    {
        $date = new DateTime();
        $user = new User('test', [], [
            'Email'      => 'a@b.com',
            'LastLogin'  => $date->format(DateTimeInterface::ATOM),
            'NeedsReset' => strtotime('now')
        ]);

        $this->assertIsArray($user->getDetails());
        $this->assertCount(3, $user->getDetails());
        $this->assertArrayHasKey('LastLogin', $user->getDetails());
        $this->assertArrayHasKey('NeedsReset', $user->getDetails());
    }

    /** @test */
    public function needs_reset_is_false_when_not_supplied()
    {
        $date = new DateTime();
        $user = new User('test', [], [
            'Email'      => 'a@b.com',
            'LastLogin'  => $date->format(DateTimeInterface::ATOM),
        ]);

        $this->assertIsArray($user->getDetails());
        $this->assertCount(3, $user->getDetails());
        $this->assertArrayHasKey('LastLogin', $user->getDetails());
        $this->assertArrayHasKey('NeedsReset', $user->getDetails());
        $this->assertFalse($user->getDetail('NeedsReset'));
    }

    /** @test */
    public function it_returns_expected_roles()
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertIsArray($user->getRoles());
        $this->assertCount(0, $user->getRoles());
    }

    /** @test */
    public function it_returns_a_valid_lastLogin_time()
    {
        // this is needed to ensure our datetime is truncated at 0 microseconds
        // which is what we store and pass it around as everywhere but this test
        $date = new DateTime();
        $date = $date->format(DateTimeInterface::ATOM);
        $date = new DateTime($date);

        $user = new User('test', [], [
            'Email'      => 'a@b.com',
            'LastLogin'  => $date->format(DateTimeInterface::ATOM),
        ]);

        $this->assertEquals($date, $user->getDetail('LastLogin'));
    }

    /** @test */
    public function it_returns_a_valid_lastLogin_time_when_constructed_from_DateTime_array()
    {
        $date = new DateTime();

        $user = new User(
            '
            test', [],
            [
                'LastLogin' => [
                    'date' => $date->format('Y-m-d H:i:s.u'),
                    'timezone_type' => 3,
                    'timezone' => $date->getTimezone()->getName(),
                ],
                'Email' => 'a@b.com',
            ],
        );

        $this->assertEquals($date, $user->getDetail('LastLogin'));
    }

    /** @test */
    public function it_returns_a_default_detail_when_provided_and_necessary()
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertEquals('Never', $user->getDetail('LastLogin', 'Never'));
    }

    /** @test */
    public function it_returns_a_null_when_property_not_found()
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertNull($user->getDetail('TestProperty'));
    }

    /** @test */
    public function it_returns_a_null_lastLogin_when_none_provided()
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertNull($user->getDetail('LastLogin'));
    }

    /** @test */
    public function it_returns_the_correct_identity()
    {
        $user = new User('test', [], ['Email' => 'test@email.com']);

        $this->assertEquals('test', $user->getIdentity());
    }
}
