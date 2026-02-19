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

        $result   = $resolver->resolveLabel('hw', 'M123456');
        $resultEC = $resolver->resolveEventCode('hw');

        $this->assertEquals('personal welfare', $result);
        $this->assertEquals('ADDED_LPA_TYPE_HW', $resultEC);
    }

    #[Test]
    public function testDigitalPropertyAndFinance(): void
    {
        $resolver = new LpaTypeResolver();

        $result   = $resolver->resolveLabel('pfa', 'M987654');
        $resultEC = $resolver->resolveEventCode('pfa');

        $this->assertEquals('property and affairs', $result);
        $this->assertEquals('ADDED_LPA_TYPE_PFA', $resultEC);
    }
}
