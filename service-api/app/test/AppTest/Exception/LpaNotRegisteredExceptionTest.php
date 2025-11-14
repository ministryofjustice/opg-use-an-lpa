<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\LpaNotRegisteredException;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaNotRegisteredExceptionTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $sut = new LpaNotRegisteredException($additionalData);

        $this->assertSame('Bad Request', $sut->getTitle());
        $this->assertSame($additionalData, $sut->getAdditionalData());

        $this->assertSame('LPA status is not registered', $sut->getMessage());
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $sut->getCode());
    }
}
