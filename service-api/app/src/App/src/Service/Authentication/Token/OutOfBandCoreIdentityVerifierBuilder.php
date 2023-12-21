<?php

declare(strict_types=1);

namespace App\Service\Authentication\Token;

use Facile\OpenIDClient\Client\ClientInterface;
use Jose\Component\Core\JWK;
use Psr\Clock\ClockInterface;

class OutOfBandCoreIdentityVerifierBuilder
{
    public function __construct(
        private string $issuer,
        private ClockInterface $clock,
    ) {
    }

    public function build(ClientInterface $client, JWK $outOfBandSigningKey): OutOfBandCoreIdentityVerifier
    {
        $clientMetadata = $client->getMetadata()->toArray();

        return new OutOfBandCoreIdentityVerifier(
            $outOfBandSigningKey,
            $this->issuer,
            $clientMetadata['client_id'],
            $this->clock,
        );
    }
}
