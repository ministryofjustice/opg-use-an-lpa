<?php

declare(strict_types=1);

namespace CommonTest\Service\OneLogin;

use Common\Service\OneLogin\AuthenticationData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthenticationDataTest extends TestCase
{
    #[Test]
    public function it_can_be_created_and_serialised(): void
    {
        $data = [
            'state'   => 'test',
            'customs' => ['test'],
        ];

        $sut = AuthenticationData::fromArray($data);

        $this->assertSame($data['state'], $sut->state);
        $this->assertSame($data['customs'], $sut->customs);

        $asJson = json_encode($sut);
        $this->assertJsonStringEqualsJsonString('{"state":"test","customs":["test"]}', $asJson);
    }
}
