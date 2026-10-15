<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\RequestGlobalsExtension;
use Common\View\Twig\RequestGlobalsRuntime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

#[CoversClass(RequestGlobalsExtension::class)]
final class RequestGlobalsExtensionTest extends TestCase
{
    #[Test]
    public function it_should_return_the_exported_twig_functions(): void
    {
        $extension = new RequestGlobalsExtension();
        $functions = $extension->getFunctions();

        $expectedFunctions = [
            'user'            => [
                RequestGlobalsRuntime::class,
                'getUser',
            ],
            'flash'           => [
                RequestGlobalsRuntime::class,
                'getFlashMessages',
            ],
            'system_messages' => [
                RequestGlobalsRuntime::class,
                'getSystemMessages',
            ],
        ];

        self::assertCount(count($expectedFunctions), $functions);

        foreach ($functions as $function) {
            self::assertInstanceOf(TwigFunction::class, $function);
            self::assertArrayHasKey($function->getName(), $expectedFunctions);
            self::assertSame($expectedFunctions[$function->getName()], $function->getCallable());
            unset($expectedFunctions[$function->getName()]);
        }

        self::assertSame([], $expectedFunctions);
    }
}
