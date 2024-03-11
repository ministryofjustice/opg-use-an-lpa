<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use AppTest\OidcUtilities;
use Aws\Command;
use Aws\Result;
use Aws\ResultInterface;
use Behat\Behat\Context\Context;
use BehatTest\Context\BaseAcceptanceContextTrait;
use BehatTest\Context\SetupEnv;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OidcContext implements Context
{
    use BaseAcceptanceContextTrait;
    use SetupEnv;

    public string $oneLoginClientPrivateKey;
    public string $oneLoginClientPublicKey;
    public string $oneLoginIssuerPublicKey;
    public string $oneLoginOutOfBandPublicKey;
    public string $accessToken = '12345';
    public string $clientId    = 'client-id';
    public string $nonce       = 'nonce1234';
    public string $sub         = 'urn:fdc:gov.uk:2022:unique_id';
    public string $email       = 'test@example.com';
    public string $birthday    = '1970-01-01';

    protected function coreIdentityTokenSetup(): string
    {
        [$token, $this->oneLoginOutOfBandPublicKey] =
            OidcUtilities::generateCoreIdentityToken($this->sub, $this->birthday);

        return $token;
    }

    protected function identityTokenSetup(): string
    {
        [$token, $this->oneLoginIssuerPublicKey] = OidcUtilities::generateIdentityToken(
            $this->sub,
            $this->clientId,
            $this->nonce,
        );

        return $token;
    }

    protected function oidcFixtureSetup(bool $withCache = false): void
    {
        // if caching is turned on then the fixtures below will be in memory already
        if ($withCache) {
            return;
        }

        apcu_clear_cache();

        [$this->oneLoginClientPrivateKey, $this->oneLoginClientPublicKey] = OidcUtilities::generateKeyPair(
            [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );

        // AbstractKeyPairManager::fetchKeyPairFromSecretsManager()
        $this->awsFixtures->append(
            function (Command $command): ResultInterface {
                Assert::assertEquals('GetSecretValue', $command->getName());
                Assert::assertEquals('gov_uk_onelogin_identity_public_key', $command['SecretId']);

                return new Result(['SecretString' => $this->oneLoginClientPublicKey]);
            }
        );

        // AbstractKeyPairManager::fetchKeyPairFromSecretsManager()
        $this->awsFixtures->append(
            function (Command $command): ResultInterface {
                Assert::assertEquals('GetSecretValue', $command->getName());
                Assert::assertEquals('gov_uk_onelogin_identity_private_key', $command['SecretId']);

                return new Result(['SecretString' => $this->oneLoginClientPrivateKey]);
            }
        );

        // AuthorisationClientManager::get()
        $this->apiFixtures->append(
            function (RequestInterface $request): ResponseInterface {
                Assert::assertEquals('/.well-known/openid-configuration', $request->getUri()->getPath());

                return new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'authorization_endpoint' => 'https://one-login-mock/authorize',
                            'issuer'                 => 'https://one-login-mock',
                            'token_endpoint'         => 'https://one-login-mock/token',
                            'userinfo_endpoint'      => 'https://one-login-mock/userinfo',
                            'jwks_uri'               => 'https://one-login-mock/.well-known/jwks',
                        ],
                    ),
                );
            },
        );
    }

    /**
     * Verifies that the supplied JWS is correctly signed
     *
     * @param string $jws A JSON Web Signature
     * @param string $signingKey A key that can be used to verify the signature. The one of the pair that
     *                           *was not* used to do the signing.
     * @return array Claims contained in the signed JWT
     */
    protected function verifyTokenSignature(string $jws, string $signingKey): array
    {
        $jwsVerifier = new JWSVerifier(new AlgorithmManager([new RS256(), new ES256()]));

        $jwk               = JWKFactory::createFromKey($signingKey);
        $serializerManager = new JWSSerializerManager([new CompactSerializer()]);

        $jws = $serializerManager->unserialize($jws);

        Assert::assertTrue($jwsVerifier->verifyWithKey($jws, $jwk, 0));

        return json_decode($jws->getPayload(), true);
    }

    /**
     * @Then /^I am redirected to the one login service$/
     */
    public function iAmRedirectedToTheOneLoginService(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('state', $response);
        Assert::assertArrayHasKey('nonce', $response);
        Assert::assertArrayHasKey('url', $response);

        Assert::assertIsString($response['state']);
        Assert::assertIsString($response['nonce']);

        $url = parse_url($response['url']);
        Assert::assertSame(
            'https://one-login-mock/authorize',
            sprintf(
                '%s://%s%s',
                $url['scheme'],
                $url['host'],
                $url['path'],
            )
        );

        parse_str($url['query'], $query);
        Assert::assertIsString($query['client_id']);
        Assert::assertSame('openid email', $query['scope']);
        Assert::assertSame('code', $query['response_type']);
        Assert::assertSame('http://sut', $query['redirect_uri']);
        Assert::assertSame($response['state'], $query['state']);
        Assert::assertSame($response['nonce'], $query['nonce']);
        Assert::assertSame('["Cl.Cm"]', $query['vtr']);
        Assert::assertSame('en', $query['ui_locales']);
    }

    /**
     * @When /^I am returned to the use an lpa service$/
     */
    public function iAmReturnedToTheUseAnLpaService(): void
    {
        $this->oidcFixtureSetup();

        /** @link AuthorisationService::callback() */
        $this->apiFixtures->append(
            function (RequestInterface $request): ResponseInterface {
                Assert::assertSame('/token', $request->getUri()->getPath());

                $request->getBody()->rewind();
                parse_str($request->getBody()->getContents(), $data);

                Assert::assertSame('authorization_code', $data['grant_type']);
                Assert::assertSame('1234', $data['code']);
                Assert::assertSame('https://sut', $data['redirect_uri']);
                Assert::assertSame(
                    'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                    $data['client_assertion_type'],
                );

                $this->verifyTokenSignature($data['client_assertion'], $this->oneLoginClientPrivateKey);

                return new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'access_token' => $this->accessToken,
                            'token_type'   => 'Bearer',
                            'id_token'     => $this->identityTokenSetup(),
                        ],
                    ),
                );
            },
        );

        /** @link AuthorisationService::callback() */
        // Call to fetch issuer signing certificate for id_token
        $this->apiFixtures->append(
            function (RequestInterface $request): ResponseInterface {
                Assert::assertSame('/.well-known/jwks', $request->getUri()->getPath());

                return new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'keys' => [
                                JWKFactory::createFromKey($this->oneLoginIssuerPublicKey),
                            ],
                        ],
                    ),
                );
            },
        );

        /**
         * @link AuthorisationService::callback()
         * Call to fetch user identity
         */
        $this->apiFixtures->append(
            function (RequestInterface $request): ResponseInterface {
                Assert::assertSame('/userinfo', $request->getUri()->getPath());
                Assert::assertSame('Bearer ' . $this->accessToken, $request->getHeader('authorization')[0]);

                return new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'sub'                                             => $this->sub,
                            'email'                                           => $this->email,
                            'email_verified'                                  => true,
                            'phone'                                           => '01406946277',
                            'phone_verified'                                  => true,
                            'updated_at'                                      => time(),
                            'https://vocab.account.gov.uk/v1/coreIdentityJWT' => $this->coreIdentityTokenSetup(),
                        ],
                    ),
                );
            },
        );

        /** @link ActorUsers::getByIdentity() */
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id'        => '0000-00-00-00-000',
                                'Identity'  => $this->sub,
                                'Email'     => $this->email,
                                'Password'  => 'password',
                                'LastLogin' => (new DateTimeImmutable('-1 day'))->format('c'),
                            ]
                        ),
                    ],
                ],
            ),
        );

        /** @link ActorUsers::recordSuccessfulLogin() */
        $this->awsFixtures->append(new Result([]));

        $this->apiPost(
            '/v1/auth/callback',
            [
                'code'         => '1234',
                'state'        => '1234',
                'auth_session' => [
                    'state'   => '1234',
                    'nonce'   => $this->nonce,
                    'customs' => [
                        'redirect_uri' => 'https://sut',
                    ],
                ],
            ],
        );
    }

    /**
     * @Then /^I am taken to my dashboard$/
     */
    public function iAmTakenToMyDashboard(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('Id', $response);
        Assert::assertArrayHasKey('Identity', $response);
        Assert::assertArrayHasKey('Email', $response);
        Assert::assertArrayHasKey('LastLogin', $response);

        Assert::assertArrayNotHasKey('Password', $response);

        Assert::assertSame($response['Identity'], $this->sub);
        Assert::assertSame($response['Email'], $this->email);
    }

    /**
     * @Given /^I have an existing local account$/
     */
    public function iHaveAnExistingLocalAccount(): void
    {
        // Not needed in this context
    }

    /**
     * @Given /^I have completed a successful one login sign\-in process$/
     */
    public function iHaveCompletedASuccessfulOneLoginSignInProcess(): void
    {
        // Not needed in this context
    }

    /**
     * @When /^I start the login process$/
     */
    public function iStartTheLoginProcess(): void
    {
        $this->oidcFixtureSetup();

        $this->apiGet('/v1/auth/start?redirect_url=http://sut&ui_locale=en');
    }

    /**
     * @Given /^I wish to login to the use an lpa service$/
     */
    public function iWishToLoginToTheUseAnLpaService(): void
    {
        // Not needed in this context
    }
}
