<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Entity\User;
use DateTime;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /** @test */
    public function it_can_be_constructed()
    {
        $user = new User('test', [], []);

        $this->assertInstanceOf(User::class, $user);
    }

    /** @test */
    public function it_returns_valid_user_details()
    {
        $date = new DateTime();
        $user = new User('test', [], ['LastLogin' => $date]);

        $this->assertIsArray($user->getDetails());
        $this->assertCount(1, $user->getDetails());
        $this->assertArrayHasKey('lastLogin', $user->getDetails());
    }

    /** @test */
    public function it_returns_expected_roles()
    {
        $user = new User('test', [], []);

        $this->assertIsArray($user->getRoles());
        $this->assertCount(0, $user->getRoles());
    }

    /** @test */
    public function it_returns_a_valid_user_detail()
    {
        $date = new DateTime();
        $user = new User('test', [], ['LastLogin' => $date]);

        $this->assertEquals($date, $user->getDetail('lastLogin'));
    }

    /** @test */
    public function it_returns_a_default_detail_when_needed()
    {
        $date = new DateTime('1 hour ago');
        $user = new User('test', [], []);

        $this->assertEquals($date, $user->getDetail('LastLogin', $date));
    }


    /** @test */
    public function it_returns_the_correct_identity()
    {
        $user = new User('test', [], []);

        $this->assertEquals('test', $user->getIdentity());
    }
}
