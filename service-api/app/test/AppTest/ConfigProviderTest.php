<?php

declare(strict_types=1);

namespace AppTest;

use App\ConfigProvider;
use App\DataAccess\ApiGateway\CachedDataStoreLpasDelegatorFactory;
use App\DataAccess\ApiGateway\CachedSiriusLpasDelegatorFactory;
use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Repository\AuditableLpasInterface;
use App\DataAccess\Repository\LpasInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function it_should_register_the_lpa_caching_delegators(): void
    {
        $dependencies = (new ConfigProvider())->getDependencies();

        self::assertSame(SiriusLpas::class, $dependencies['aliases'][LpasInterface::class]);
        self::assertSame(
            DataStoreLpas::class,
            $dependencies['aliases'][AuditableLpasInterface::class],
        );
        self::assertContains(
            CachedSiriusLpasDelegatorFactory::class,
            $dependencies['delegators'][SiriusLpas::class],
        );
        self::assertContains(
            CachedDataStoreLpasDelegatorFactory::class,
            $dependencies['delegators'][DataStoreLpas::class],
        );
    }
}
