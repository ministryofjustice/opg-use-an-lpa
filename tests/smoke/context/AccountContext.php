<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Context;

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
            $this->ui->assertPageAddress('/authorize');
            $this->ui->fillField('email', $this->userEmail);
            $this->ui->pressButton('Continue');
        } else {
            $this->ui->assertPageAddress('/login');
            $this->ui->fillField('email', $this->userEmail);
            $this->ui->fillField('password', $this->userPassword);
            $this->ui->pressButton('Sign in');
        }
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
}
