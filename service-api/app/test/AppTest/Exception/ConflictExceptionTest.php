<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Exception\ConflictException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConflictExceptionTest extends TestCase
{
    #[Test]
    public function dataGets(): void
    {
        $message = 'ce message';

        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $ce = new ConflictException($message, $additionalData);

        $this->assertEquals('Conflict', $ce->getTitle());
        $this->assertEquals($additionalData, $ce->getAdditionalData());

        $this->assertEquals($message, $ce->getMessage());
        $this->assertEquals(409, $ce->getCode());
    }

    #[Test]
    public function it_narrows_scope_of_logged_data(): void
    {
        $additionalData = [
            'identity' => 'identity',
            'email'    => 'test@example.com',
            'some'     => 'additional',
            'data'     => 'here,',
        ];

        $sut = new ConflictException('', $additionalData);

        $this->assertCount(2, $sut->getAdditionalDataForLogging());
        $this->assertArrayHasKey('identity', $sut->getAdditionalDataForLogging());
        $this->assertEquals('identity', $sut->getAdditionalDataForLogging()['identity']);
        $this->assertArrayHasKey('email', $sut->getAdditionalDataForLogging());
        $this->assertEquals(
            '973dfe463ec85785f5f95af5ba3906eedb2d931c24e69824a89ea65dba4e813b', // pragma: allowlist secret
            (string) $sut->getAdditionalDataForLogging()['email']
        );
    }
}
