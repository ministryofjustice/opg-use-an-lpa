<?php

declare(strict_types=1);

namespace CommonTest\Entity;

use Common\Entity\UserFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use Zend\Expressive\Authentication\UserInterface;

class UserFactoryTest extends TestCase
{
    /** @test */
    public function it_returns_a_valid_callable()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new UserFactory();

        $callable = $factory($containerProphecy->reveal());

        $this->assertIsCallable($callable);

        $r = new ReflectionFunction($callable);
        $parameters = $r->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('string', ($parameters[0])->getType());
        $this->assertEquals('array', ($parameters[1])->getType());
        $this->assertEquals('array', ($parameters[2])->getType());

        $this->assertEquals(UserInterface::class, $r->getReturnType());
    }

    /** @test */
    public function the_callable_generates_a_user()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new UserFactory();

        $callable = $factory($containerProphecy->reveal());

        $user = $callable('test', [], []);

        $this->assertInstanceOf(UserInterface::class, $user);
        $this->assertEquals('test', $user->getIdentity());
    }
}
