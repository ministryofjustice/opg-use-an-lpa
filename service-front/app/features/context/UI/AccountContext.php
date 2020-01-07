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

/**
 * A behat context that encapsulates user account steps
 *
 * Account creation, login, password reset etc.
 *
 * @property string activationToken
 * @property string email
 * @property string password
 * @property array lpas
 */

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
     * @Given /^I am not a user of the lpa application$/
     */
    public function iAmNotAUserOfTheLpaApplication()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I want to create a new account$/
     */
    public function iWantToCreateANewAccount()
    {
        $this->iAmOnHomepage();
        $this->assertPageContainsText('Create an account');

        $this->pressButton('Create an account');
    }

    /**
     * @When /^I create an account$/
     */
    public function iCreateAnAccount()
    {
        $this->email = 'test@example.com';
        $this->password = 'n3wPassWord';
        $this->activationToken = 'activate1234567890';

        $this->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Email'           => $this->email,
                'ActivationToken' => $this->activationToken,
            ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->fillField('email', $this->email);
        $this->fillField('email_confirm', $this->email);
        $this->fillField('password', $this->password);
        $this->fillField('password_confirm', $this->password);
        $this->fillField('terms', 1);
        $this->pressButton('Create account');
    }

    /**
     * @Then /^I receive unique instructions on how to create an account$/
     */
    public function iReceiveUniqueInstructionsOnHowToCreateAnAccount()
    {
        $this->assertPageAddress('/create-account-success');

        $this->assertPageContainsText('We\'ve emailed a link to ' . $this->email);

        assertInternalType('string', $this->activationToken);
        assertEquals(true, $this->apiFixtures->isEmpty());
    }

    /**
     * @Given /^I have asked for creating new account$/
     */
    public function iHaveAskedForCreatingNewAccount()
    {
        $this->email = 'test@example.com';
        $this->password = 'n3wPassWord';
        $this->activationToken = 'activate1234567890';
    }

    /**
     * @When /^I follow the instructions on how to activate my account$/
     */
    public function iFollowTheInstructionsOnHowToActivateMyAccount()
    {
        // API fixture for reset token check
        $this->apiFixtures->patch('/v1/user-activation')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'activation_token' => $this->activationToken])));

        $this->visit('/activate-account/' . $this->activationToken);
    }

    /**
     * @Then /^my account is activated$/
     */
    public function myAccountIsActivated()
    {
        $this->assertPageContainsText('Account activated');
        $this->assertPageContainsText('sign in');
    }

    /**
     * @When /^I sign in$/
     */
    public function iSignIn()
    {
        $this->visit('/login');
        $this->assertPageAddress('/login');
        $this->assertPageContainsText('Continue');

        // API call for password reset request
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Id'        => '123',
                'Email'     => $this->email,
                'LastLogin' => null
            ])));

        // Dashboard page checks for all LPA's for a user
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpas)));

        $this->fillField('email', $this->email);
        $this->fillField('password', $this->password);
        $this->pressButton('Continue');
    }

    /**
     * @Then /^I should be taken to the new users first page$/
     */
    public function iShouldBeTakenToTheNewUsersFirstPage()
    {

        $this->assertPageAddress('/lpa/add-details');
    }

    /**
     * @When /^I follow my unique instructions after 24 hours$/
     */
    public function iFollowMyUniqueInstructionsAfter24Hours()
    {
        // remove successful reset token and add failure state
        $this->apiFixtures->patch('/v1/user-activation')
            ->respondWith(new Response(StatusCodeInterface::STATUS_NOT_FOUND));

        $this->visit('/activate-account/' . $this->activationToken);
    }

    /**
     * @Then /^I am told my unique instructions to activate my account have expired$/
     */
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired()
    {
        throw new PendingException();

       // $this->assertPageAddress('/activate-account-not-found');
       // $this->assertPageContainsText('You created the account more than 24 hours ago');
    }

    /**
     * @When /^I create an account using duplicate details$/
     */
    public function iCreateAnAccountUsingDuplicateDetails()
    {
        $this->email = 'test@example.com';
        $this->password = 'n3wPassWord';
        $this->activationToken = 'activate1234567890';

        $this->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_CONFLICT, [], json_encode([
                'Email'           => $this->email,
                'ActivationToken' => $this->activationToken,
            ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->fillField('email', $this->email);
        $this->fillField('email_confirm', $this->email);
        $this->fillField('password', $this->password);
        $this->fillField('password_confirm', $this->password);
        $this->fillField('terms', 1);
        $this->pressButton('Create account');
    }



    /**
     * @Then /^I am directed to login page$/
     */
    public function iAmDirectedToLoginPage()
    {
        $this->assertPageAddress('/');
        $this->assertPageContainsText('Sign in');
        $this->pressButton('Sign in');
    }

    /**
     * @Then /^I am told my account details not recognised$/
     */
    public function iAmToldMyAccountDetailsNotRecognised()
    {
        $this->assertPageAddress('/login');
        $this->assertPageContainsText('There is a problem');
        $this->assertPageContainsText('Email and password combination not recognised. Please try signing in again below or create an account');
    }



    /**
     * @Given /^I am unable to continue to create my account$/
     */
    public function iAmUnableToContinueToCreateMyAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be taken to the first page$/
     */
    public function iShouldBeTakenToTheFirstPage()
    {
        $this->assertPageAddress('/lpa/dashboard');

       // $this->assertPageContainsText('Add a lasting power of attorney');
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