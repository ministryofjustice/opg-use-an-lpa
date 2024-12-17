<?php

namespace AppTest\Service\Lpa;

use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\CombinedLpaManager;
use App\Service\Lpa\LpaManagerFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class CombinedLpaManagerTest extends TestCase
{
    use ProphecyTrait;

    private CombinedLpaManager $combinedLpaManager;
    public function setUp(): void
    {
        $mockFeatureEnabled = $this->createMock(FeatureEnabled::class);
        $mockFeatureEnabled->method('__invoke')->willReturn(true);

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->method('get')
            ->willReturnMap(
                [
                    [FeatureEnabled::class, $mockFeatureEnabled],
                    [CombinedLpaManager::class, $this->createMock(CombinedLpaManager::class)],
                ]
            );

        $factory = new LpaManagerFactory($mockContainer);

        $this->combinedLpaManager = ($factory)();
    }

    #[Test]
    public function can_get_by_uid_test() {

        $this->assertInstanceOf(CombinedLpaManager::class, $this->combinedLpaManager);
    }

    #[Test]
    public function can_get_by_user_lpa_actor_token_test() {
    }

    #[Test]
    public function can_get_all_active_for_user_test() {
    }

    #[Test]
    public function can_get_all_for_user_test() {
    }

    #[Test]
    public function can_get_by_viewer_code_test() {
    }
}