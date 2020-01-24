<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Alphagov\Notifications\Client;
use BehatTest\Context\ActorContextTrait as ActorContext;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Class AccountContext
 *
 * @package BehatTest\Context\UI
 *
 * @property $userEmail
 * @property $userPassword
 */
class AccountContext extends BaseUIContext
{
    use ActorContext;

    /**
     * @Given /^I am a user of the lpa application$/
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->ui->iAmOnHomepage();

        $this->ui->clickLink('Sign in');
    }

    /**
     * @Given /^I have forgotten my password$/
     */
    public function iHaveForgottenMyPassword()
    {
        $this->ui->assertPageAddress('/login');

        $this->ui->clickLink('Forgotten your password?');
    }

    /**
     * @When /^I ask for my password to be reset$/
     */
    public function iAskForMyPasswordToBeReset()
    {
        $this->ui->assertPageAddress('/forgot-password');

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'PasswordResetToken' => '123456' ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('email_address', $params);
                    assertArrayHasKey('personalisation', $params);

                    assertInternalType('array', $params['personalisation']);
                    assertArrayHasKey('password-reset-url', $params['personalisation']);
                }
            );

        $this->ui->fillField('email', 'test@example.com');
        $this->ui->fillField('email_confirm', 'test@example.com');
        $this->ui->pressButton('Email me the link');
    }

    /**
     * @Then /^I receive unique instructions on how to reset my password$/
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->ui->assertPageAddress('/forgot-password');

        $this->ui->assertPageContainsText('We\'ve emailed a link to test@example.com');

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
        $this->ui->visit('/forgot-password/123456');

        $this->ui->assertPageContainsText('Change your password');
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

        $this->ui->visit('/forgot-password/123456');
    }

    /**
     * @Given /^I choose a new password$/
     */
    public function iChooseANewPassword()
    {
        $this->ui->assertPageAddress('/forgot-password/123456');

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

        $this->ui->fillField('password', 'n3wPassWord');
        $this->ui->fillField('password_confirm', 'n3wPassWord');
        $this->ui->pressButton('Change password');
    }

    /**
     * @Then /^my password has been associated with my user account$/
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
    {
        $this->ui->assertPageAddress('/login');
        // TODO when flash message are in place
        //$this->assertPageContainsText('Password successfully reset');

        assertEquals(true, $this->apiFixtures->isEmpty());
    }

    /**
     * @Then /^I am told that my instructions have expired$/
     */
    public function iAmToldThatMyInstructionsHaveExpired()
    {
        $this->ui->assertPageAddress('/forgot-password/123456');

        $this->ui->assertPageContainsText('invalid or has expired');
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
        $this->ui->assertPageAddress('/forgot-password/123456');

        // API fixture for reset token check
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'Id' => '123456' ])));

        $this->ui->fillField('password', $password);
        $this->ui->fillField('password_confirm', $password);
        $this->ui->pressButton('Change password');
    }

    /**
     * @Then /^I am told that my password is invalid because it needs at least (.*)$/
     */
    public function iAmToldThatMyPasswordIsInvalidBecauseItNeedsAtLeast($reason)
    {
        $this->ui->assertPageAddress('/forgot-password/123456');

        $this->ui->assertPageContainsText('at least ' . $reason);
    }

    /**
     * @Given /^I am signed in$/
     */
    public function iSignIn()
    {
        $this->userEmail = 'test@test.com';
        $this->userPassword = 'pa33w0rd';

        $this->ui->visit('/login');
        $this->ui->assertPageAddress('/login');
        $this->ui->assertElementContainsText('button[type=submit]', 'Continue');

        // API call for password reset request
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Id'        => '123',
                'Email'     => $this->userEmail,
                'LastLogin' => null
            ])));

        // Dashboard page checks for all LPA's for a user
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);
        $this->ui->pressButton('Continue');

        // ---

        $this->ui->assertPageAddress('/lpa/add-details');
    }

    /**
     * @When /^I view my user details$/
     */
    public function iViewMyUserDetails()
    {
        $this->ui->visit('/your-details');
        $this->ui->assertPageContainsText('Your details');
    }

    /**
     * @Then /^I can change my email if required$/
     */
    public function iCanChangeMyEmailIfRequired()
    {
        $this->ui->assertPageAddress('/your-details');

        $this->ui->assertPageContainsText('Email address');
        $this->ui->assertPageContainsText($this->userEmail);

        $session = $this->getSession();
        $page = $session->getPage();

        $changeEmailText = 'Change email address';
        $link = $page->findLink($changeEmailText);
        if ($link === null) {
            throw new \Exception($changeEmailText . ' link not found');
        }
    }

    /**
     * @Then /^I can change my passcode if required$/
     */
    public function iCanChangeMyPasscodeIfRequired()
    {
        $this->ui->assertPageAddress('/your-details');

        $this->ui->assertPageContainsText('Password');

        $session = $this->getSession();
        $page = $session->getPage();

        $changePasswordtext = "Change password";
        $link = $page->findLink($changePasswordtext);
        if ($link === null) {
            throw new \Exception($changePasswordtext . ' link not found');
        }
    }

    /**
     * @When /^I ask for a change of donors or attorneys details$/
     */
    public function iAskForAChangeOfDonorsOrAttorneysDetails()
    {
        $this->ui->assertPageAddress('/your-details');

        $this->ui->assertPageContainsText('Change a donor\'s or attorney\'s details');
        $this->ui->clickLink('Change a donor\'s or attorney\'s details');
    }

    /**
     * @Then /^Then I am given instructions on how to change donor or attorney details$/
     */
    public function iAmGivenInstructionOnHowToChangeDonorOrAttorneyDetails()
    {
        $this->ui->assertPageAddress('/lpa/change-details');

        $this->ui->assertPageContainsText('Let us know if a donor\'s or attorney\'s details change');
        $this->ui->assertPageContainsText('Find out more');
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
        $this->ui->iAmOnHomepage();
        $this->ui->pressButton('Create an account');
    }

    /**
     * @When /^I create an account$/
     */
    public function iCreateAnAccount()
    {
        $this->email = 'test@example.com';
        $this->password = 'n3wPassWord';
        $this->activationToken = 'activate1234567890';

        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Email'           => $this->email,
                'ActivationToken' => $this->activationToken,
            ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $this->email);
        $this->ui->fillField('email_confirm', $this->email);
        $this->ui->fillField('password', $this->password);
        $this->ui->fillField('password_confirm', $this->password);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    /**
     * @Then /^I receive unique instructions on how to activate my account$/
     */
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount()
    {
        $this->ui->assertPageAddress('/create-account-success');

        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->email);

        assertInternalType('string', $this->activationToken);
        assertEquals(true, $this->apiFixtures->isEmpty());
    }

    /**
     * @Given I have asked to create a new account
    */
    public function iHaveAskedToCreateANewAccount()
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

        $this->ui->visit('/activate-account/' . $this->activationToken);
    }

    /**
     * @Then /^my account is activated$/
     */
    public function myAccountIsActivated()
    {
        $this->ui->assertPageContainsText('Account activated');
        $this->ui->assertPageContainsText('sign in');
    }

    /**
     * @When /^I follow my unique instructions after 24 hours$/
     */
    public function iFollowMyUniqueInstructionsAfter24Hours()
    {
        // remove successful reset token and add failure state
        $this->apiFixtures->patch('/v1/user-activation')
            ->respondWith(new Response(StatusCodeInterface::STATUS_NOT_FOUND));

        $this->ui->visit('/activate-account/' . $this->activationToken);
    }

    /**
     * @Then /^I am told my unique instructions to activate my account have expired$/
     */
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired()
    {
        $this->activationToken = 'activate1234567890';
        $this->ui->assertPageAddress('/activate-account/'. $this->activationToken);
        $this->ui->assertPageContainsText('You created the account more than 24 hours ago');
    }

    /**
     * @When /^I create an account using duplicate details$/
     */
    public function iCreateAnAccountUsingDuplicateDetails()
    {
        $this->email = 'test@example.com';
        $this->password = 'n3wPassWord';
        $this->activationToken = 'activate1234567890';

        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_CONFLICT, [], json_encode([
                'Email'           => $this->email,
                'ActivationToken' => $this->activationToken,
            ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $this->email);
        $this->ui->fillField('email_confirm', $this->email);
        $this->ui->fillField('password', $this->password);
        $this->ui->fillField('password_confirm', $this->password);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    /**
     * @When /^I have not provided required information for account creation such as (.*)(.*)(.*)(.*)(.*)$/
     */
    public function iHaveNotProvidedRequiredInformationForAccountCreationSuchAs($email1,$email2,$password1,$password2,$terms)
    {
        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $email1);
        $this->ui->fillField('email_confirm', $email2);
        $this->ui->fillField('password', $password1);
        $this->ui->fillField('password_confirm', $password2);

        $this->ui->pressButton('Create account');
    }

    /**
     * @Then /^I should be told my account could not be created due to (.*)$/
     */
    public function iShouldBeToldMyAccountCouldNotBeCreatedDueTo($reasons)
    {
        $this->ui->assertPageAddress('/create-account');

        $this->ui->assertPageContainsText('' . $reasons);
    }

    /**
     * @When /^Creating account I provide mismatching (.*) (.*)$/
     */
    public function CreatingAccountIProvideMismatching($value1, $value2)
    {
        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $value1);
        $this->ui->fillField('email_confirm', $value2);
        $this->ui->fillField('password',  $value1);
        $this->ui->fillField('password_confirm', $value2);

        $this->ui->pressButton('Create account');
    }
}