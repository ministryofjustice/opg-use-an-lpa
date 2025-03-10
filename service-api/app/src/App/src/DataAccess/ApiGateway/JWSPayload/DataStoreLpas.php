<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway\JWSPayload;

use App\Service\InternalClock;
use App\Service\JWT\JWSPayload;
use Psr\Clock\ClockInterface;

class DataStoreLpas implements JWSPayload
{
    private string $payload;

    public function __construct(string $identifier, ClockInterface $clock = new InternalClock())
    {
        $now = $clock->now()->getTimestamp();

        $this->payload = json_encode(
            [
                'iat' => $now,
                'nbf' => $now,
                'exp' => $now + 3600,
                'iss' => 'opg.poas.use',
                'sub' => 'urn:opg:poas:use:users:' . $identifier,
            ]
        );
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}
