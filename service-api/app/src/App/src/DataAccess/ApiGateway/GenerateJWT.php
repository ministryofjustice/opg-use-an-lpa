<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Service\JWT\JWKFactory;
use App\Service\JWT\JWSBuilderFactory;
use App\Service\JWT\JWSPayload;
use App\Service\Secrets\SecretManagerInterface;
use Jose\Component\Signature\Serializer\CompactSerializer;

class GenerateJWT
{
    public function __construct(
        private JWSBuilderFactory $builderFactory,
        private JWKFactory $jwkFactory,
    ) {
    }

    public function __invoke(SecretManagerInterface $secretManager, JWSPayload $payload): string
    {
        $jwk        = ($this->jwkFactory)($secretManager);
        $jwsBuilder = $this->builderFactory->create(['HS256']);

        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload->getPayload())
            ->addSignature($jwk, ['alg' => $jwk->get('alg')])
            ->build();

        $serializer = new CompactSerializer();

        return $serializer->serialize($jws, 0);
    }
}
