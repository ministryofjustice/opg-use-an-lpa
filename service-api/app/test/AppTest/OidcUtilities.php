<?php

declare(strict_types=1);

namespace AppTest;

use Exception;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;

class OidcUtilities
{
    public static function generateCoreIdentityToken(string $sub, string $birthday): array
    {
        $token = json_encode(
            [
                'iss' => 'http://identity.one-login-mock/',
                'sub' => $sub,
                'exp' => time() + 300,
                'iat' => time(),
                'nbf' => time(),
                'vc'  => [
                    'type'              => [
                        'VerifiableCredential',
                        'VerifiableIdentityCredential',
                    ],
                    'credentialSubject' => [
                        'birthDate' => [
                            ['value' => $birthday],
                        ],
                    ],
                ],
            ],
        );

        [$private, $public] = self::generateKeyPair(
            [
                'curve_name'       => 'prime256v1',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
            ],
        );

        return [
            self::signToken($token, $private),
            $public,
        ];
    }

    /**
     * Generates an elliptic-curve keypair
     *
     * @param array $options
     * @return array{
     *     string,
     *     string,
     * } A private and public key
     * @throws Exception
     */
    public static function generateKeyPair(array $options): array
    {
        $key = openssl_pkey_new($options);
        if ($key === false) {
            throw new Exception('Unable to create the identity key');
        }

        $details = openssl_pkey_get_details($key);
        if (! is_array($details)) {
            throw new Exception('Unable to get key details');
        }

        $success = openssl_pkey_export($key, $privateKey);
        if (!$success) {
            throw new Exception('Unable to export key to string');
        }

        /** @var array{string, string} */
        return [$privateKey, $details['key']];
    }

    /**
     * Signs a JWT with an ES256 algorithm
     *
     * @param string $payload A json encoded array structure representing a JWT.
     * @param string $key A public or private key in PEM format. Must be an EC key.
     * @return string
     */
    public static function signToken(string $payload, string $key): string
    {
        $jwsBuilder = (new JWSBuilder(new AlgorithmManager([new ES256()])))
            ->create()
            ->withPayload($payload)
            ->addSignature(JWKFactory::createFromKey($key), ['alg' => 'ES256'])
            ->build();

        return (new CompactSerializer())->serialize($jwsBuilder, 0);
    }
}
