<?php

declare(strict_types=1);

namespace CommonTest\Service\Features;

use Common\Service\Features\FeatureEnabled;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class FeatureEnabledTest
 *
 * @package CommonTest\Service\Features
 * @coversDefaultClass \Common\Service\Features\FeatureEnabled
 */
class FeatureEnabledTest extends TestCase
{
    /**
     * @test
     * @covers ::__invoke
     */
    public function it_correctly_returns_feature_value_from_configuration(): void
    {
        $flags = [
            'feature_name' => true
        ];

        $sut = new FeatureEnabled($flags);

        $enabled = $sut('feature_name');

        $this->assertTrue($enabled);
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_throws_an_exception_when_not_finding_a_feature_value(): void
    {
        $flags = [
            'feature_name' => true
        ];

        $sut = new FeatureEnabled($flags);

        $this->expectException(RuntimeException::class);
        $enabled = $sut('other_feature_name');
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_throws_an_exception_when_not_finding_badly_configured_feature_value(): void
    {
        $flags = [
            'feature_name' => 'True'
        ];

        $sut = new FeatureEnabled($flags);

        $this->expectException(RuntimeException::class);
        $enabled = $sut('other_feature_name');
    }
}
