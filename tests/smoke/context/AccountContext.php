<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Context;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use OTPHP\TOTP;

class AccountContext implements Context
{
    use BaseContextTrait;

    public string $userEmail;
    public string $userPassword;
    public string $lpaReference;
    public string $lpaActivationKey;

    /** @var array{
     *     'day': int,
     *     'month': int,
     *     'year': int
     * }
     */
    public array $userDob;

    /**
     * @Given I am a user of the lpa application
     */
    public function iAmAUserOfTheLpaApplication(): void
    {
        $this->userEmail    = 'opg-use-an-lpa+test-user@digital.justice.gov.uk';
        $this->userPassword = 'umlTest1';
    }

    /**
     * @Given I have been given access to use an LPA via credentials
     */
    public function iHaveBeenGivenAccessToUseAnLpaViaCredentials(): void
    {
        $this->lpaReference     = '700000000047';
        $this->lpaActivationKey = 'RY4KKKVMRVAK';
        $this->userDob          = [
            'day'   => 5,
            'month' => 10,
            'year'  => 1975,
        ];
    }

    /**
     * @Given I access the login form
     */
    public function iAccessTheLoginForm(): void
    {
        if ($this->featureFlags['allow_gov_one_login'] ?? false) {
            $this->ui->visit('/home');
            $this->ui->pressButton('sign-in-one-login');
        } else {
            $this->ui->visit('/login');
        }
    }

    /**
     * @When I enter correct credentials
     */
    public function iEnterCorrectCredentials(): void
    {
        if ($this->featureFlags['allow_gov_one_login'] ?? false) {
            switch ($this->detectOneLoginImplementation()) {
                case OneLoginImplementation::Mock:
                    $this->ui->assertPageAddress('/authorize');
                    $this->ui->fillField('email', $this->userEmail);
                    break;
                case OneLoginImplementation::Integration:
                case OneLoginImplementation::Production:
                    $this->ui->pressButton('sign-in-button');

                    $this->ui->fillField('email', $this->userEmail);
                    $this->ui->pressButton('Continue');

                    $this->userPassword = getenv('ONE_LOGIN_USER_PASSWORD')
                        ? getenv('ONE_LOGIN_USER_PASSWORD')
                        : throw new Exception('ONE_LOGIN_USER_PASSWORD is needed for testing against One Login');

                    $this->ui->fillField('password', $this->userPassword);
                    $this->ui->pressButton('Continue');

                    // Generate a 2fa secret just before use.
                    // There is a non-zero chance it will be incorrect if generated at the end of its 30-second window
                    $secret = getenv('ONE_LOGIN_OTP_SECRET')
                        ? getenv('ONE_LOGIN_OTP_SECRET')
                        : throw new Exception('ONE_LOGIN_OTP_SECRET is needed for testing against One Login');

                    $this->ui->fillField('code', TOTP::createFromSecret($secret)->now());
            }

            $this->ui->pressButton('Continue');
        } else {
            $this->ui->assertPageAddress('/login');
            $this->ui->fillField('email', $this->userEmail);
            $this->ui->fillField('password', $this->userPassword);
            $this->ui->pressButton('Sign in');
        }
    }

    private function detectOneLoginImplementation(): OneLoginImplementation
    {
        // the one login integration environment will have given us a basic auth dialog to fill.
        // the driver we're using doesn't give us great tools to work with that, so we'll just brute force it
        if (
            $this->ui->getSession()->getStatusCode() === StatusCodeInterface::STATUS_UNAUTHORIZED
            && $this->ui->getSession()->getResponseHeader('www-authenticate') !== null
        ) {
            $credentials           = getenv('ONE_LOGIN_CREDENTIALS')
                ? getenv('ONE_LOGIN_CREDENTIALS')
                : throw new Exception('ONE_LOGIN_CREDENTIALS is needed for testing against One Login');
            [$username, $password] = explode(':', $credentials, 2);

            $this->ui->getSession()->setBasicAuth($username, $password);
            $this->ui->getSession()->reload();
            $this->ui->assertPageAddress('/sign-in-or-create');

            return OneLoginImplementation::Integration;
        }

        if ($this->ui->getSession()->getPage()->hasButton('create-account-link')) {
            $this->ui->assertPageAddress('/sign-in-or-create');
            return OneLoginImplementation::Production;
        }

        return OneLoginImplementation::Mock;
    }

    /**
     * @Then I am signed in
     */
    public function iAmSignedIn(): void
    {
        if ($this->featureFlags['allow_gov_one_login'] ?? false) {
            $this->ui->assertElementOnPage('nav.one-login-header__nav');
        } else {
            $this->ui->assertElementOnPage('nav.signin');
        }
    }

    /**
     * @Then the javascript is working
     */

    public function scriptsWork(): void
    {
        if(!$this->ui->getSession()->evaluateScript("return window.useAnLPALoaded")){
            throw new ExpectationException(
                'Javascript did not parse without errors',
                $this->ui->getMink()->getSession()->getDriver()
            );
        }
    }
}
