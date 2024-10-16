<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Common\Service\Features\FeatureEnabled;
use Common\View\Twig\FeatureFlagExtension;
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
        $extension = new FeatureFlagExtension(
            $this->prophesize(FeatureEnabled::class)->reveal()
        );

        $functions = $extension->getFunctions();

        $this->assertIsArray($functions);

        $expectedFunctions = [
            'feature_enabled' => 'featureEnabled',
        ];
        $this->assertEquals(count($expectedFunctions), count($functions));

        //  Check each function
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertArrayHasKey($function->getName(), $expectedFunctions);

            $functionCallable = $function->getCallable();
            $this->assertInstanceOf(FeatureFlagExtension::class, $functionCallable[0]);
            $this->assertEquals($expectedFunctions[$function->getName()], $functionCallable[1]);
        }
    }

    #[DataProvider('configuredFeatures')]
    #[Test]
    public function it_returns_the_features_configured_status(string $featureName, bool $enabled): void
    {
        $service = $this->prophesize(FeatureEnabled::class);
        $service
            ->__invoke($featureName)
            ->willReturn($enabled);

        $extension = new FeatureFlagExtension($service->reveal());

        $result = $extension->featureEnabled($featureName);

        $this->assertEquals($enabled, $result);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function configuredFeatures(): array
    {
        return [
            'feature enabled'
                => [
                    'test_feature',
                    false,
                ],
            'feature disabled'
                => [
                    'test_feature',
                    true,
                ],
        ];
    }
}
