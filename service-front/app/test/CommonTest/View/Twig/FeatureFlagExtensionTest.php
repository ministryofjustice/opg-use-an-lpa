<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\View\Twig\FeatureFlagExtension;
use Common\View\Twig\FeatureFlagRuntime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Twig\TwigFunction;

#[CoversClass(FeatureFlagExtension::class)]
class FeatureFlagExtensionTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_returns_an_array_of_exported_twig_functions(): void
    {
        $extension = new FeatureFlagExtension();

        $functions = $extension->getFunctions();

        $this->assertIsArray($functions);

        $expectedFunctions = [
            'feature_enabled' => FeatureFlagRuntime::class . '::featureEnabled',
        ];
        $this->assertEquals(count($expectedFunctions), count($functions));

        //  Check each function
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertArrayHasKey($function->getName(), $expectedFunctions);

            $functionCallable = $function->getCallable();
            $this->assertEquals($expectedFunctions[$function->getName()], $functionCallable);
        }
    }
}
