<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Context;

/**
 * Class AccountContext
 *
 * @package Test\Context
 *
 * @property string userEmail
 * @property string userPassword
 * @property string lpaReference
 * @property string lpaActivationKey
 * @property int[] userDob
 */
class AccountContext implements Context
{
    use BaseContextTrait;

    /**
     * @Given I am a user of the lpa application
     */
    public function iAmAUserOfTheLpaApplication(): void
    {
        $this->userEmail = 'opg-use-an-lpa+test-user@digital.justice.gov.uk';
        $this->userPassword = 'umlTest1';
    }

    /**
     * @Given I have been given access to use an LPA via credentials
     */
    public function iHaveBeenGivenAccessToUseAnLpaViaCredentials(): void
    {
        $this->lpaReference = '700000000047';
        $this->lpaActivationKey = 'RY4KKKVMRVAK';
        $this->userDob = [
            'day' => 5,
            'month' => 10,
            'year' => 1975
        ];
    }

    /**
     * @Given I access the login form
     */
    public function iAccessTheLoginForm(): void
    {
        $this->ui->visit('/login');
    }

    /**
     * @When I enter correct credentials
     */
    public function iEnterCorrectCredentials(): void
    {
        $this->ui->assertPageAddress('/login');

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);

        $this->ui->pressButton('Sign in');
    }

    /**
     * @Then I am signed in
     */
    public function iAmSignedIn(): void
    {
        $this->ui->assertElementOnPage('div.signin > nav.navigation');
    }
}
