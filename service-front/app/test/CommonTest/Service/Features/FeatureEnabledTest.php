<?php

declare(strict_types=1);

namespace CommonTest\Service\Features;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Common\Service\Features\FeatureEnabled;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(FeatureEnabled::class)]
class FeatureEnabledTest extends TestCase
{
    #[Test]
    public function it_correctly_returns_feature_value_from_configuration(): void
    {
        $flags = [
            'feature_name' => true,
        ];

        $sut = new FeatureEnabled($flags);

        $enabled = $sut('feature_name');

        $this->assertTrue($enabled);
    }

    #[Test]
    public function it_throws_an_exception_when_not_finding_a_feature_value(): void
    {
        $flags = [
            'feature_name' => true,
        ];

        $sut = new FeatureEnabled($flags);

        $this->expectException(RuntimeException::class);
        $enabled = $sut('other_feature_name');
    }

    #[Test]
    public function it_throws_an_exception_when_not_finding_badly_configured_feature_value(): void
    {
        $flags = [
            'feature_name' => 'Yes',
        ];

        $sut = new FeatureEnabled($flags);

        $this->expectException(RuntimeException::class);
        $enabled = $sut('feature_name');
    }
}
