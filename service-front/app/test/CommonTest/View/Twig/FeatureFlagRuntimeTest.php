<?php

declare(strict_types=1);

namespace CommonTest\View\Twig;

use Common\Service\Features\FeatureEnabled;
use Common\View\Twig\FeatureFlagRuntime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[CoversClass(FeatureFlagRuntime::class)]
class FeatureFlagRuntimeTest extends TestCase
{
    use ProphecyTrait;

    #[DataProvider('configuredFeatures')]
    #[Test]
    public function it_returns_the_features_configured_status(string $featureName, bool $enabled): void
    {
        $service = $this->prophesize(FeatureEnabled::class);
        $service
            ->__invoke($featureName)
            ->willReturn($enabled);

        $runtime = new FeatureFlagRuntime($service->reveal());

        $result = $runtime->featureEnabled($featureName);

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
