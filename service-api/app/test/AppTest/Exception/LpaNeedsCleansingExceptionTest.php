<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\LpaNeedsCleansingException;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaNeedsCleansingExceptionTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $sut = new LpaNeedsCleansingException($additionalData);

        $this->assertEquals('Bad Request', $sut->getTitle());
        $this->assertEquals($additionalData, $sut->getAdditionalData());

        $this->assertEquals('LPA needs cleansing', $sut->getMessage());
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $sut->getCode());
    }

    #[Test]
    public function it_narrows_scope_of_logged_data(): void
    {
        $additionalData = [
            'actor_id' => '1234',
            'some'     => 'additional',
            'data'     => 'here,',
        ];

        $sut = new LpaNeedsCleansingException($additionalData);

        $this->assertEquals(
            [
                'actor_id' => '1234',
            ],
            $sut->getAdditionalDataForLogging(),
        );
    }
}
