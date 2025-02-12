<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway\JWSPayload;

use App\DataAccess\ApiGateway\JWSPayload\DataStoreLpas;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class DataStoreLpasTest extends TestCase
{
    #[Test]
    public function it_creates_an_appropriately_formatted_jwt_payload(): void
    {
        $now = new DateTimeImmutable();

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        $jwt = new DataStoreLpas('my_user_identifier', $clock);

        $payload = json_decode($jwt->getPayload());
        $this->assertCount(5, (array)$payload);
        $this->assertEquals($now->getTimestamp(), $payload->iat);
        $this->assertEquals($now->getTimestamp(), $payload->nbf);
        $this->assertEquals($now->getTimestamp() + 3600, $payload->exp);
        $this->assertEquals('opg.poas.use', $payload->iss);
        $this->assertEquals('urn:opg:poas:use:users:my_user_identifier', $payload->sub);
    }
}
