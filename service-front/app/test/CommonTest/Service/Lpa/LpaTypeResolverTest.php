<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Service\Lpa\LpaTypeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaTypeResolverTest extends TestCase
{
    #[Test]
    public function testDigitalHealthWelfare(): void
    {
        $resolver = new LpaTypeResolver();

        $result = $resolver->resolveLabel('hw', 'M123456');

        $this->assertEquals('personal welfare', $result);
    }
}
