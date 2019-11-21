<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Aws\Result;
use BehatTest\Context\ActorContextTrait as ActorContext;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use function random_bytes;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

class AccountContext extends BaseUIContext
{
    use ActorContext;

    /**
     * @BeforeScenario
     */
    public function seedFixtures()
    {
        // KMS is polled for encryption data on first page load
        $this->awsFixtures->append(
            new Result([
                'Plaintext' => random_bytes(32),
                'CiphertextBlob' => random_bytes(32)
            ])
        );
    }

    /**
     * @Given /^I am a user of the lpa application$/
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->iAmOnHomepage();

        $this->clickLink('Sign in');
    }

    /**
     * @Given /^I have forgotten my password$/
     */
    public function iHaveForgottenMyPassword()
    {
        $this->assertPageAddress('/login');

        $this->clickLink('Forgotten your password?');
    }

    /**
     * @When /^I ask for my password to be reset$/
     */
    public function iAskForMyPasswordToBeReset()
    {
        $this->assertPageAddress('/forgot-password');

        // API call for password reset request
        $mockHandler = $this->container->get(MockHandler::class);
        $mockHandler->append(new Response(200, [], json_encode([ 'PasswordResetToken' => '123456' ])));

        // API call for Notify
        $mockHandler->append(new Response(200, [], json_encode([])));

        $this->fillField('email', 'test@example.com');
        $this->fillField('email_confirm', 'test@example.com');
        $this->pressButton('Email me the link');
    }

    /**
     * @Then /^I receive unique instructions on how to reset my password$/
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->assertPageAddress('/forgot-password');

        $this->assertPageContainsText('We\'ve emailed a link to test@example.com');

        // verify we've used all the expected mock responses
        $mockHandler = $this->container->get(MockHandler::class);
        assertEquals($mockHandler->count(), 0);
    }

    /**
     * @Given /^I have asked for my password to be reset$/
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        // Not needed for this context
    }

    /**
     * @When /^I follow my unique instructions on how to reset my password$/
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @When /^I follow my unique expired instructions on how to reset my password$/
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword()
    {
    }

    /**
     * @Given /^I choose a new password$/
     */
    public function iChooseANewPassword()
    {
    }

    /**
     * @Then /^my password has been associated with my user account$/
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^my password has not been associated with my user account$/
     */
    public function myPasswordHasNotBeenAssociatedWithMyUserAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told that my instructions have expired$/
     */
    public function iAmToldThatMyInstructionsHaveExpired()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am unable to continue to reset my password$/
     */
    public function iAmUnableToContinueToResetMyPassword()
    {
        // Not needed for this context
    }
}