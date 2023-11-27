<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Aws\Command;
use Aws\Result;
use Aws\ResultInterface;
use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Testwork\Suite\Exception\SuiteSetupException;
use BehatTest\Context\BaseAcceptanceContextTrait;
use BehatTest\Context\SetupEnv;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OidcContext implements Context
{
    use BaseAcceptanceContextTrait;
    use SetupEnv;

    private ?string $one_login_client_private_key;
    private ?string $one_login_client_public_key;

    /**
     * @beforeScenario @onelogin
     */
    public function generateRSAKeyPair()
    {
        $key = openssl_pkey_new(
            [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );
        if ($key === false) {
            throw new SuiteSetupException('Unable to create the key', 'onelogin');
        }

        $details = openssl_pkey_get_details($key);
        if (! is_array($details)) {
            throw new SuiteSetupException('Unable to get key details', 'onelogin');
        }

        $success = openssl_pkey_export($key, $this->one_login_client_private_key);
        if (!$success) {
            throw new SuiteSetupException('Unable to export key to string', 'onelogin');
        }

        $this->one_login_client_public_key = $details['key'];
    }

    /**
     * @Then /^I am redirected to the one login service$/
     */
    public function iAmRedirectedToTheOneLoginService()
    {
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

        // AbstractKeyPairManager::fetchKeyPairFromSecretsManager()
        $this->awsFixtures->append(
            function (Command $command): ResultInterface {
                Assert::assertEquals('GetSecretValue', $command->getName());
                Assert::assertEquals('gov_uk_onelogin_identity_public_key', $command['SecretId']);

                return new Result(['SecretString' => $this->one_login_client_public_key]);
            }
        );

        // AbstractKeyPairManager::fetchKeyPairFromSecretsManager()
        $this->awsFixtures->append(
            function (Command $command): ResultInterface {
                Assert::assertEquals('GetSecretValue', $command->getName());
                Assert::assertEquals('gov_uk_onelogin_identity_private_key', $command['SecretId']);

                return new Result(['SecretString' => $this->one_login_client_private_key]);
            }
        );

        $this->apiGet('/v1/auth/start?redirect_url=http://sut&ui_locale=en');

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
        Assert::assertSame('["Cl.Cm.P2"]', $query['vtr']);
        Assert::assertSame('en', $query['ui_locales']);
        Assert::assertSame(
            '{"userinfo":{"https://vocab.account.gov.uk/v1/coreIdentityJWT":null}}',
            $query['claims']
        );
    }

    /**
     * @When /^I am returned to the use an lpa service$/
     */
    public function iAmReturnedToTheUseAnLpaService(): void
    {
        // Not needed in this context
    }

    /**
     * @Then /^I am taken to my dashboard$/
     */
    public function iAmTakenToMyDashboard(): void
    {
        // Not needed in this context
    }

    /**
     * @Given /^I have an existing local account$/
     */
    public function iHaveAnExistingLocalAccount(): void
    {
        throw new PendingException();
    }

    /**
     * @Given /^I have completed a successful one login sign\-in process$/
     */
    public function iHaveCompletedASuccessfulOneLoginSignInProcess()
    {
        throw new PendingException();
    }

    /**
     * @When /^I start the login process$/
     */
    public function iStartTheLoginProcess()
    {
        // Not needed in this context
    }

    /**
     * @Given /^I wish to login to the use an lpa service$/
     */
    public function iWishToLoginToTheUseAnLpaService()
    {
        // Not needed in this context
    }
}
