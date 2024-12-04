<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\LpaDetailsDoNotMatchException;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaDetailsDoNotMatchExceptionTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $sut = new LpaDetailsDoNotMatchException($additionalData);

        $this->assertEquals('Bad Request', $sut->getTitle());
        $this->assertEquals($additionalData, $sut->getAdditionalData());

        $this->assertEquals('LPA details do not match', $sut->getMessage());
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $sut->getCode());
    }

    #[Test]
    public function it_narrows_scope_of_logged_data(): void
    {
        $additionalData = [
            'lpaRegDate' => '2024-12-02',
            'some'       => 'additional',
            'data'       => 'here,',
        ];

        $sut = new LpaDetailsDoNotMatchException($additionalData);

        $this->assertEquals(
            [
                'lpaRegDate' => '2024-12-02',
            ],
            $sut->getAdditionalDataForLogging(),
        );
    }
}
