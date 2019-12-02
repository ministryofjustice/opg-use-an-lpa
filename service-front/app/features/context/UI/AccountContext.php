<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Alphagov\Notifications\Client;
use Aws\Result;
use Behat\Behat\Tester\Exception\PendingException;
use BehatTest\Context\ActorContextTrait as ActorContext;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
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
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'PasswordResetToken' => '123456' ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

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

        assertEquals(true, $this->apiFixtures->isEmpty());
    }

    /**
     * @Given /^I have asked for my password to be reset$/
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        // API fixture for reset token check
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'Id' => '123456' ])));
    }

    /**
     * @When /^I follow my unique instructions on how to reset my password$/
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->visit('/forgot-password/123456');

        $this->assertPageContainsText('Change your password');
    }

    /**
     * @When /^I follow my unique expired instructions on how to reset my password$/
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword()
    {
        // remove successful reset token and add failure state
        $this->apiFixtures->getHandlers()->pop();
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_GONE));

        $this->visit('/forgot-password/123456');
    }

    /**
     * @Given /^I choose a new password$/
     */
    public function iChooseANewPassword()
    {
        $this->assertPageAddress('/forgot-password/123456');

        // API fixture for reset token check
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'Id' => '123456' ])));

        // API fixture for password reset
        $this->apiFixtures->patch('/v1/complete-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'Id' => '123456' ])))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $params = json_decode($request->getBody()->getContents(), true);

                assertInternalType('array', $params);
                assertArrayHasKey('token', $params);
                assertArrayHasKey('password', $params);
            });

        $this->fillField('password', 'n3wPassWord');
        $this->fillField('password_confirm', 'n3wPassWord');
        $this->pressButton('Change password');
    }

    /**
     * @Then /^my password has been associated with my user account$/
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
    {
        $this->assertPageAddress('/login');
        // TODO when flash message are in place
        //$this->assertPageContainsText('Password successfully reset');

        assertEquals(true, $this->apiFixtures->isEmpty());
    }

    /**
     * @Then /^I am told that my instructions have expired$/
     */
    public function iAmToldThatMyInstructionsHaveExpired()
    {
        $this->assertPageAddress('/forgot-password/123456');

        $this->assertPageContainsText('invalid or has expired');
    }

    /**
     * @Given /^I am unable to continue to reset my password$/
     */
    public function iAmUnableToContinueToResetMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I choose a new invalid password of "(.*)"$/
     */
    public function iChooseANewInvalid($password)
    {
        $this->assertPageAddress('/forgot-password/123456');

        // API fixture for reset token check
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'Id' => '123456' ])));

        $this->fillField('password', $password);
        $this->fillField('password_confirm', $password);
        $this->pressButton('Change password');
    }

    /**
     * @Then /^I am told that my password is invalid because it needs at least (.*)$/
     */
    public function iAmToldThatMyPasswordIsInvalidBecauseItNeedsAtLeast($reason)
    {
        $this->assertPageAddress('/forgot-password/123456');

        $this->assertPageContainsText('at least ' . $reason);
    }
}