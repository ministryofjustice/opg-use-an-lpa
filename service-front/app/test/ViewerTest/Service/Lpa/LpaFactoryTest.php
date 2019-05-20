<?php

declare(strict_types=1);

namespace ViewerTest\Service\Lpa;

use PHPUnit\Framework\TestCase;
use Viewer\Service\Lpa\LpaFactory;

class LpaFactoryTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testBadDataThrowsException()
    {
        $factory = new LpaFactory();

        $this->expectException(\Zend\Stdlib\Exception\InvalidArgumentException::class);
        $lpa = $factory->createLpaFromData([]);
    }
}