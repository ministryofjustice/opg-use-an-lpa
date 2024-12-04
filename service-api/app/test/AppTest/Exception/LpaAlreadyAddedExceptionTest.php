<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\LpaAlreadyAddedException;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaAlreadyAddedExceptionTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $sut = new LpaAlreadyAddedException($additionalData);

        $this->assertEquals('Bad Request', $sut->getTitle());
        $this->assertEquals($additionalData, $sut->getAdditionalData());

        $this->assertEquals('LPA already added', $sut->getMessage());
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $sut->getCode());
    }

    #[Test]
    public function it_narrows_scope_of_logged_data(): void
    {
        $additionalData = [
            'donor'                => [
                'uId' => '700000000047',
            ],
            'caseSubtype'          => 'pfa',
            'activationKeyDueDate' => '2020-12-31',
            'some'                 => 'additional',
            'data'                 => 'here,',
        ];

        $sut = new LpaAlreadyAddedException($additionalData);

        $this->assertEquals(
            [
                'donor'                => [
                    'uId' => '700000000047',
                ],
                'caseSubtype'          => 'pfa',
                'activationKeyDueDate' => '2020-12-31',
            ],
            $sut->getAdditionalDataForLogging(),
        );
    }
}
