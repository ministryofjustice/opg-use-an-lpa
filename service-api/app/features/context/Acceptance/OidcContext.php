<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use App\DataAccess\Cache\SessionLpaCache;
use App\DataAccess\DynamoDb\ActorUsers;
use App\DataAccess\Repository\Response\Lpa;
use App\Service\Lpa\LpaDataFormatter;
use App\Service\User\ResolveOAuthUser;
use AppTest\OidcUtilities;
use Aws\Command;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\Result;
use Aws\ResultInterface;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\BaseAcceptanceContextTrait;
use BehatTest\Context\SetupEnv;
use Closure;
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

    public const LOGIN_TYPE = [
        'NEW_ACCOUNT'               => 0, // completely new account
        'MIGRATING_USER'            => 1, // local 'identityless' account and unknown one login - email will match
        'EXISTING_USER'             => 2, // just a normal login
        'EXISTING_USER_NEW_EMAIL'   => 3, // one login supplying a new email
        'EXISTING_USER_NEW_SUBJECT' => 4, // an unknown one login account has an email we know of
    ];

    public string $oneLoginClientPrivateKey;
    public string $oneLoginClientPublicKey;
    public string $oneLoginIssuerPublicKey;
    public string $oneLoginOutOfBandPublicKey;
    public string $accessToken     = '12345';
    public string $clientId        = 'client-id';
    public string $nonce           = 'nonce1234';
    public string $identityEmail   = '';
    public string $identitySubject = '';
    public int $type               = 0;
    private string $cachedSessionId;
    private string $cachedLpaUid;
    private bool $hasOrphanIdentityPin = false;
    private int $identityWriteAttempts = 0;
    private string $writtenAccountId;

    /**
     * @var array{
     *  Id: string,
     *  Identity?: string,
     *  Email: string,
     *  LastLogin: string
     * }
     */
    public array $localAccount        = [];
    public ?Closure $oneLoginIdentity = null;

    protected function coreIdentityTokenSetup(): string
    {
        [
            $token,
            $this->oneLoginOutOfBandPublicKey,
        ] =
            OidcUtilities::generateCoreIdentityToken($this->identitySubject, '1970-01-01');

        return $token;
    }

    protected function identityTokenSetup(): string
    {
        [
            $token,
            $this->oneLoginIssuerPublicKey,
        ] = OidcUtilities::generateIdentityToken(
            $this->identitySubject,
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

        [
            $this->oneLoginClientPrivateKey,
            $this->oneLoginClientPublicKey,
        ] = OidcUtilities::generateKeyPair(
            [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );

        /** @see AbstractKeyPairManager::fetchKeyPairFromSecretsManager() */
        $this->awsFixtures->append(
            function (Command $command): ResultInterface {
                Assert::assertEquals('GetSecretValue', $command->getName());
                Assert::assertEquals('gov-uk-onelogin-identity-public-key', $command['SecretId']);

                return new Result(['SecretString' => $this->oneLoginClientPublicKey]);
            }
        );

        /** @see AbstractKeyPairManager::fetchKeyPairFromSecretsManager() */
        $this->awsFixtures->append(
            function (Command $command): ResultInterface {
                Assert::assertEquals('GetSecretValue', $command->getName());
                Assert::assertEquals('gov-uk-onelogin-identity-private-key', $command['SecretId']);

                return new Result(['SecretString' => $this->oneLoginClientPrivateKey]);
            }
        );

        /** @see AuthorisationClientManager::get() */
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
                            'end_session_endpoint'   => 'https://one-login-mock/logout',
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

        return json_decode((string) $jws->getPayload(), true);
    }

    #[Given('I have a local account')]
    public function iHaveALocalAccount(): void
    {
        $this->localAccount = [
            'Id'        => '0000-00-00-00-000',
            'Identity'  => 'urn:fdc:gov.uk:2022:unique_id',
            'Email'     => 'test@example.com',
            'LastLogin' => (new DateTimeImmutable('-1 day'))->format('c'),
        ];
    }

    #[Given('I do not have a local account')]
    public function iDoNotHaveALocalAccount(): void
    {
        $this->localAccount = [];
    }

    #[Given('I have an unknown One Login identity')]
    public function iHaveAnUnknownOneLoginIdentity(): void
    {
        $this->identityEmail   = 'test@example.com';
        $this->identitySubject = 'urn:fdc:gov.uk:2022:unique_id';

        if (count($this->localAccount) > 0) {
            $this->type = self::LOGIN_TYPE['MIGRATING_USER'];
        } else {
            $this->type = self::LOGIN_TYPE['NEW_ACCOUNT'];
        }

        $this->fixIdentity();
    }

    #[Given('I have a matching One Login identity')]
    #[Given('the identity provided by One Login has a :type')]
    public function iHaveAMatchingOneLoginIdentity(string $type = ''): void
    {
        $this->identityEmail   = 'test@example.com';
        $this->identitySubject = 'urn:fdc:gov.uk:2022:unique_id';

        switch ($type) {
            case '':
                $this->type = self::LOGIN_TYPE['EXISTING_USER'];
                break;
            case 'new email':
                $this->identityEmail = 'new.email@example.com';
                $this->type          = self::LOGIN_TYPE['EXISTING_USER_NEW_EMAIL'];
                break;
            case 'different subject than expected':
                $this->identitySubject = 'urn:fdc:gov.uk:2022:new_unique_id';
                $this->type            = self::LOGIN_TYPE['EXISTING_USER_NEW_SUBJECT'];
                break;
        }

        $this->fixIdentity();
    }

    #[Given('my One Login identity has an orphan identity pin')]
    public function myOneLoginIdentityHasAnOrphanIdentityPin(): void
    {
        $this->hasOrphanIdentityPin = true;
    }

    #[When('I complete a One Login sign-in process')]
    public function iCompleteAOneLoginSignInProcess(): void
    {
        $this->oidcFixtureSetup();

        /** @see  AuthorisationService::callback() */
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

        /**
         * @see AuthorisationService::callback()
         * Call to fetch issuer signing certificate for id_token
         */
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
         * @see AuthorisationService::callback()
         * Call to fetch user identity
         */
        $this->apiFixtures->append($this->oneLoginIdentity);

        switch ($this->type) {
            case self::LOGIN_TYPE['EXISTING_USER_NEW_EMAIL']:
            case self::LOGIN_TYPE['EXISTING_USER']:
                // an identity exists and matches (email may differ)
                $this->fetchByIdentityFixtures();
                break;
            case self::LOGIN_TYPE['EXISTING_USER_NEW_SUBJECT']:
                // the identity does not exist (email may match, record may have different identity)
                $this->fetchByEmailFixtures(true);
                break;
            case self::LOGIN_TYPE['MIGRATING_USER']:
                // the identity does not exist (email may match, record may have different identity)
                $this->fetchByEmailFixtures(false);
                break;
            case self::LOGIN_TYPE['NEW_ACCOUNT']:
                $this->newUserFixtures();
                break;
        }

        /** @see  ActorUsers::recordSuccessfulLogin() */
        $this->awsFixtures->append(function (Command $command): ResultInterface {
            if ($this->hasOrphanIdentityPin) {
                Assert::assertSame('UpdateItem', $command->getName());
                Assert::assertSame(['Id' => ['S' => $this->writtenAccountId]], $command['Key']);
            }

            return new Result([]);
        });
    }

    #[Then('/^I am redirected to the one login service$/')]
    public function iAmRedirectedToTheOneLoginService(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('state', $response);
        Assert::assertArrayHasKey('nonce', $response);
        Assert::assertArrayHasKey('url', $response);

        Assert::assertIsString($response['state']);
        Assert::assertIsString($response['nonce']);

        $url = parse_url((string) $response['url']);
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

    #[Then('/^I am returned to the use an lpa service$/')]
    public function iAmReturnedToTheUseAnLpaService(): void
    {
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

    #[Then('/^I am taken to complete a satisfaction survey$/')]
    public function iAmTakenToCompleteASatisfactionSurvey(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('redirect_uri', $response);
    }

    #[Then('/^the login process is a success$/')]
    public function theLoginProcessIsASuccess(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('user', $response);
        Assert::assertArrayHasKey('token', $response);

        $user = $response['user'];
        Assert::assertArrayHasKey('Id', $user);
        Assert::assertArrayHasKey('Identity', $user);
        Assert::assertArrayHasKey('Email', $user);

        Assert::assertSame($user['Identity'], $this->identitySubject);
        Assert::assertSame($user['Email'], $this->identityEmail);
    }

    #[Then('the orphan identity pin has been recovered')]
    public function theOrphanIdentityPinHasBeenRecovered(): void
    {
        $response = $this->getResponseAsJson();

        Assert::assertSame(2, $this->identityWriteAttempts);
        Assert::assertSame($this->writtenAccountId, $response['user']['Id']);

        if ($this->type === self::LOGIN_TYPE['MIGRATING_USER']) {
            Assert::assertSame($this->localAccount['Id'], $response['user']['Id']);
        }
    }

    #[When('/^I logout of the application$/')]
    public function iLogoutOfTheApplication(): void
    {
        $this->oidcFixtureSetup();

        $this->apiPut(
            '/v1/auth/logout',
            [
                'user' => [
                    'Id'        => '0000-00-00-00-000',
                    'Identity'  => $this->identitySubject,
                    'Email'     => $this->identityEmail,
                    'LastLogin' => (new DateTimeImmutable('-1 day'))->format('c'),
                    'IdToken'   => $this->identityTokenSetup(),
                ],
            ],
        );
    }

    #[When('/^I start the login process$/')]
    public function iStartTheLoginProcess(): void
    {
        $this->oidcFixtureSetup();

        $this->apiGet('/v1/auth/start?redirect_url=http://sut&ui_locale=en');
    }

    #[Given('/^I wish to login to the use an lpa service$/')]
    public function iWishToLoginToTheUseAnLpaService(): void
    {
        // Not needed in this context
    }

    #[Given('I have LPAs cached in my session and another session')]
    public function iHaveLpasCachedInMySessionAndAnotherSession(): void
    {
        $this->cachedSessionId = bin2hex(random_bytes(16));
        $data                  = json_decode(
            file_get_contents(__DIR__ . '/../../../test/fixtures/test_lpa.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $lpa                   = new Lpa((new LpaDataFormatter())->hydrateObject($data), new DateTimeImmutable());
        $this->cachedLpaUid    = $lpa->getData()->getUid();
        $cache                 = $this->base->container->get(SessionLpaCache::class);

        $this->ui->getSession()->setRequestHeader('User-Session', $this->cachedSessionId);

        foreach ([$this->cachedSessionId, $this->cachedSessionId . '-other'] as $sessionId) {
            $cache->lookup([$this->cachedLpaUid], $sessionId, static fn (): array => [$lpa]);
        }
    }

    #[Then('only my logged out session LPA cache is cleared')]
    public function onlyMyLoggedOutSessionLpaCacheIsCleared(): void
    {
        $cache = $this->base->container->get(SessionLpaCache::class);

        try {
            Assert::assertSame([], $cache->lookup(
                [$this->cachedLpaUid],
                $this->cachedSessionId,
                static fn (): array => [],
            ));
            Assert::assertCount(1, $cache->lookup(
                [$this->cachedLpaUid],
                $this->cachedSessionId . '-other',
                static fn (): array => [],
            ));
        } finally {
            $cache->invalidateSession($this->cachedSessionId);
            $cache->invalidateSession($this->cachedSessionId . '-other');
        }
    }

    private function fetchByEmailFixtures(bool $hasIdentity = false): void
    {
        /** @see ActorUsers::getByIdentity() */
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [],
                ],
            ),
        );

        if (!$hasIdentity) {
            unset($this->localAccount['Identity']);

            /** @see ActorUsers::getByEmail() */
            $this->awsFixtures->append(
                new Result(
                    [
                        'Items' => [
                            $this->marshalAwsResultData(
                                $this->localAccount
                            ),
                        ],
                    ],
                ),
            );

            $this->identityWriteFixtures(true);
        } else {
            /** @see ActorUsers::getByEmail() */
            $this->awsFixtures->append(
                new Result(
                    [
                        'Items' => [
                            $this->marshalAwsResultData(
                                $this->localAccount
                            ),
                        ],
                    ],
                ),
            );

            /** @see ResolveOAuthUser::addNewUser() */
            $this->awsFixtures->append(
                function (Command $command): ResultInterface {
                    Assert::assertEquals('TransactWriteItems', $command->getName());

                    Assert::assertCount(2, $command['TransactItems']);

                    return new Result([]);
                }
            );
        }
    }

    private function fetchByIdentityFixtures(): void
    {
        /** @see ActorUsers::getByIdentity() */
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            $this->localAccount
                        ),
                    ],
                ],
            ),
        );

        // Email is different to expected.
        if ($this->identityEmail !== $this->localAccount['Email']) {
            /** @see ActorUsers::getByEmail() */
            $this->awsFixtures->append(
                new Result(['Items' => []]) // email not found
            );

            /** @see ResolveOAuthUser::updateEmail() */
            $this->awsFixtures->append(
                function (Command $command): ResultInterface {
                    Assert::assertEquals('UpdateItem', $command->getName());

                    return new Result([]);
                }
            );
        }
    }

    private function newUserFixtures(): void
    {
        /** @see ActorUsers::getByIdentity() */
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [],
                ],
            ),
        );

        /** @see ActorUsers::getByEmail() */
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [],
                ],
            ),
        );

        $this->identityWriteFixtures(false);
    }

    private function identityWriteFixtures(bool $migration): void
    {
        $attempts = $this->hasOrphanIdentityPin ? 2 : 1;

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $this->awsFixtures->append(function (Command $command) use ($migration): ResultInterface {
                Assert::assertSame('TransactWriteItems', $command->getName());
                Assert::assertCount(2, $command['TransactItems']);

                $items = $command['TransactItems'];
                $pin   = $items[1]['Put'];

                Assert::assertSame(['Id' => ['S' => 'IDENTITY#' . $this->identitySubject]], $pin['Item']);

                if ($this->identityWriteAttempts === 0) {
                    Assert::assertSame('attribute_not_exists(Id)', $pin['ConditionExpression']);
                } else {
                    Assert::assertArrayNotHasKey('ConditionExpression', $pin);
                }

                $this->writtenAccountId = $this->assertAccountWrite($items[0], $migration);
                $this->identityWriteAttempts++;

                if ($this->hasOrphanIdentityPin && $this->identityWriteAttempts === 1) {
                    throw new DynamoDbException('Identity pin already exists', $command, [
                        'code' => 'TransactionCanceledException',
                        'body' => [
                            'CancellationReasons' => [
                                ['Code' => 'None'],
                                ['Code' => 'ConditionalCheckFailed'],
                            ],
                        ],
                    ]);
                }

                return new Result(['@metadata' => ['statusCode' => StatusCodeInterface::STATUS_OK]]);
            });
        }
    }

    private function assertAccountWrite(array $operation, bool $migration): string
    {
        if ($migration) {
            Assert::assertSame(['Id' => ['S' => $this->localAccount['Id']]], $operation['Update']['Key']);
            Assert::assertSame('SET #sub = :sub', $operation['Update']['UpdateExpression']);
            Assert::assertSame(['#sub' => 'Identity'], $operation['Update']['ExpressionAttributeNames']);
            Assert::assertSame(
                [':sub' => ['S' => $this->identitySubject]],
                $operation['Update']['ExpressionAttributeValues'],
            );

            return $this->localAccount['Id'];
        }

        $account = $operation['Put']['Item'];

        Assert::assertSame(['S' => $this->identitySubject], $account['Identity']);
        Assert::assertSame(['S' => $this->identityEmail], $account['Email']);
        Assert::assertNotEmpty($account['Id']['S']);

        return $account['Id']['S'];
    }

    private function fixIdentity(): void
    {
        $this->oneLoginIdentity = function (RequestInterface $request): ResponseInterface {
            Assert::assertSame('/userinfo', $request->getUri()->getPath());
            Assert::assertSame('Bearer ' . $this->accessToken, $request->getHeader('authorization')[0]);

            return new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode(
                    [
                        'sub'                                             => $this->identitySubject,
                        'email'                                           => $this->identityEmail,
                        'email_verified'                                  => true,
                        'phone'                                           => '01406946277',
                        'phone_verified'                                  => true,
                        'updated_at'                                      => time(),
                        'https://vocab.account.gov.uk/v1/coreIdentityJWT' => $this->coreIdentityTokenSetup(),
                    ],
                ),
            );
        };
    }
}
