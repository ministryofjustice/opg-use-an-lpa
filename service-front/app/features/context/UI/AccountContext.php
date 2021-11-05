<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Alphagov\Notifications\Client;
use Behat\Behat\Context\Context;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * @property string userEmail
 * @property string userPassword
 * @property bool   userActive
 * @property string userId
 * @property string activationToken
 * @property string newUserEmail
 * @property string userEmailResetToken
 * @property string email
 * @property string password
 */
class AccountContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;

    /**
     * @Then /^An account is created using (.*) (.*) (.*)$/
     */
    public function anAccountIsCreatedUsingEmailPasswordTerms($email, $password, $terms)
    {
        $this->activationToken = 'activate1234567890';

        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => '123',
                            'Email' => $email,
                            'ActivationToken' => $this->activationToken,
                        ]
                    )
                )
            );

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $email);
        $this->ui->fillField('password', $password);
        $this->ui->fillField('terms', $terms);
        $this->ui->pressButton('Create account');
    }

    /**
     * @Given /^I access the account creation page$/
     */
    public function iAccessTheAccountCreationPage()
    {
        $this->ui->visit('/create-account');
        $this->ui->assertPageAddress('/create-account');
    }

    /**
     * @Given /^I access the login form$/
     */
    public function iAccessTheLoginForm()
    {
        $this->ui->visit('/login');
        $this->ui->assertPageAddress('/login');
        $this->ui->assertElementContainsText('button[name=sign-in]', 'Sign in');
    }

    /**
     * @When /^I access the use a lasting power of attorney web page$/
     */
    public function iAccessTheUseALastingPowerOfAttorneyWebPage()
    {
        $this->iAmOnTheTriagePage();
    }

    /**
     * @Given /^I am a user of the lpa application$/
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->userEmail = 'test@test.com';
        $this->userPassword = 'pa33w0rd';
        $this->userActive = true;
        $this->userId = '123';
    }

    /**
     * @Then /^I am allowed to create an account$/
     */
    public function iAmAllowedToCreateAnAccount()
    {
        $this->ui->assertPageAddress('/create-account');
        $this->ui->assertPageContainsText('Create an account');
    }

    /**
     * @Then /^I am asked to confirm whether I am sure if I want to delete my account$/
     */
    public function iAmAskedToConfirmWhetherIAmSureIfIWantToDeleteMyAccount()
    {
        $this->ui->assertPageAddress('/confirm-delete-account');
        $this->ui->assertPageContainsText('Are you sure you want to delete your account?');
    }

    /**
     * @Given /^I am currently signed in$/
     * @When /^I sign in$/
     */
    public function iAmCurrentlySignedIn()
    {
        // do all the steps to sign in
        $this->iAccessTheLoginForm();
        $this->iEnterCorrectCredentials();
        $this->iAmSignedIn();
    }

    /**
     * @Then /^I am directed to my dashboard$/
     */
    public function iAmDirectedToMyPersonalDashboard()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @Then /^Then I am given instructions on how to change donor or attorney details$/
     */
    public function iAmGivenInstructionOnHowToChangeDonorOrAttorneyDetails()
    {
        $this->ui->assertPageAddress('/lpa/change-details');
        $this->ui->assertPageContainsText('Let us know if a donor or attorney\'s details change');
    }

    /**
     * @Then /^I am informed that there was a problem with that email address$/
     */
    public function iAmInformedThatThereWasAProblemWithThatEmailAddress()
    {
        $this->ui->assertPageAddress('/create-account-success');
        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->userEmail);
    }

    /**
     * @Given /^I am logged out of the service and taken to the deleted account confirmation page$/
     */
    public function iAmLoggedOutOfTheServiceAndTakenToTheDeletedAccountConfirmationPage()
    {
        $this->ui->assertPageAddress('/delete-account');
        $this->ui->assertPageContainsText("We've deleted your account");
    }

    /**
     * @Given /^I am not a user of the lpa application$/
     */
    public function iAmNotAUserOfTheLpaApplication()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am not allowed to progress$/
     */
    public function iAmNotAllowedToProgress()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText('Select yes if you have a Use a lasting power of attorney account');
    }

    /**
     * @When /^I am not signed in to the use a lasting power of attorney service at this point$/
     */
    public function iAmNotSignedInToTheUseALastingPowerOfAttorneyServiceAtThisPoint()
    {
        $this->ui->assertPageAddress('/login');
    }

    /**
     * @Given /^I am on the actor privacy notice page$/
     */
    public function iAmOnTheActorPrivacyNoticePage()
    {
        $this->ui->visit('/privacy-notice');
        $this->ui->assertPageAddress('/privacy-notice');
    }

    /**
     * @Given /^I am on the actor terms of use page$/
     */
    public function iAmOnTheActorTermsOfUsePage()
    {
        $this->ui->visit('/terms-of-use');
        $this->ui->assertPageAddress('/terms-of-use');
    }

    /**
     * @Given /^I am on the change email page$/
     */
    public function iAmOnTheChangeEmailPage()
    {
        $this->newUserEmail = 'newEmail@test.com';
        $this->userEmailResetToken = '12345abcde';

        $this->ui->visit('/your-details');

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $link = $page->find('css', 'a[href="/change-email"]');
        if ($link === null) {
            throw new Exception('Change email link not found');
        }

        $link->click();

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/change-email');
    }

    /**
     * @Given /^I am on the confirm account deletion page$/
     */
    public function iAmOnTheConfirmAccountDeletionPage()
    {
        $this->iAmOnTheYourDetailsPage();
        $this->iRequestToDeleteMyAccount();
    }

    /**
     * @Given /^I am on the create account page$/
     */
    public function iAmOnTheCreateAccountPage()
    {
        $this->ui->visit('/create-account');
        $this->ui->assertPageAddress('/create-account');
    }

    /**
     * @When /^I am on the password reset page$/
     */
    public function iAmOnThePasswordResetPage()
    {
        $this->ui->assertPageContainsText('Reset your password');
    }

    /**
     * @Given /^I am on the stats page$/
     */
    public function iAmOnTheStatsPage()
    {
        $this->ui->visit('/stats');
    }

    /**
     * @Given /^I am on the triage page$/
     */
    public function iAmOnTheTriagePage()
    {
        $this->ui->visit('/home');
    }

    /**
     * @Given /^I am on the your details page$/
     */
    public function iAmOnTheYourDetailsPage()
    {
        $this->ui->clickLink('Your details');
    }

    /**
     * @Given /^I am signed in$/
     */
    public function iAmSignedIn()
    {
        $link = $this->ui->getSession()->getPage()->find('css', 'a[href="/logout"]');
        if ($link === null) {
            throw new Exception('Sign out link not found');
        }
    }

    /**
     * @Then /^I am taken back to the dashboard page$/
     */
    public function iAmTakenBackToTheDashboardPage()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @Then /^I am taken back to the terms of use page$/
     */
    public function iAmTakenBackToTheTermsOfUsePage()
    {
        $this->ui->assertPageAddress('/terms-of-use');
    }

    /**
     * @Then /^I am taken back to the your details page$/
     */
    public function iAmTakenBackToTheYourDetailsPage()
    {
        $this->ui->assertPageAddress('/your-details');
        $this->ui->assertPageContainsText('Your details');
    }

    /**
     * @Then /^I am taken to complete a satisfaction survey$/
     */
    public function iAmTakenToCompleteASatisfactionSurvey()
    {
        $this->ui->assertPageAddress('/done/use-lasting-power-of-attorney');
    }

    /**
     * @Then /^I am taken to the actor cookies page$/
     */
    public function iAmTakenToTheActorCookiesPage()
    {
        $this->ui->assertPageAddress('/cookies');
        $this->ui->assertPageContainsText('Use a lasting power of attorney service');
    }

    /**
     * @Then /^I am taken to the create account page$/
     */
    public function iAmTakenToTheCreateAccountPage()
    {
        $this->ui->assertPageAddress('/create-account');
        $this->ui->assertPageContainsText('Create an account');
    }

    /**
     * @Then /^I am taken to the dashboard page$/
     */
    public function iAmTakenToTheDashboardPage()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @Then /^I am allowed to login$/
     */
    public function iAmTakenToTheLoginPage()
    {
        $this->ui->assertPageAddress('/login');
        $this->ui->assertPageContainsText('Sign in to your Use a lasting power of attorney account');
    }

    /**
     * @Then /^I am taken to the session expired page$/
     */
    public function iAmTakenToTheSessionExpiredPage()
    {
        $this->ui->assertPageAddress('/session-expired');
        $this->ui->assertPageContainsText('We\'ve signed you out');
    }

    /**
     * @Then /^I am taken to the triage page of the service$/
     */
    public function iAmTakenToTheTriagePage()
    {
        $this->ui->assertPageAddress('/home');
    }

    /**
     * @Then /^I am told my account has not been activated$/
     */
    public function iAmToldMyAccountHasNotBeenActivated()
    {
        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->userEmail);
    }

    /**
     * @Then /^I am told my credentials are incorrect$/
     */
    public function iAmToldMyCredentialsAreIncorrect()
    {
        $this->ui->assertPageContainsText('We could not find a Use a lasting power of attorney account with that 
        email address and password. Check your details and try again.');
    }

    /**
     * @Then /^I am told my current password is incorrect$/
     */
    public function iAmToldMyCurrentPasswordIsIncorrect()
    {
        $this->ui->assertPageAddress('change-password');

        $this->ui->assertPageContainsText('Current password is incorrect');
    }

    /**
     * @Then /^I am told my password was changed$/
     */
    public function iAmToldMyPasswordWasChanged()
    {
        $this->ui->assertPageAddress('your-details');
    }

    /**
     * @Then /^I am told my unique instructions to activate my account have expired$/
     */
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired()
    {
        $this->activationToken = 'activate1234567890';
        $this->ui->assertPageAddress('/activate-account/' . $this->activationToken);
        $this->ui->assertPageContainsText('We could not activate that account');
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
     * @Then /^I am told that my new password is invalid because it needs at least (.*)$/
     */
    public function iAmToldThatMyNewPasswordIsInvalidBecauseItNeedsAtLeast($reason)
    {
        $this->ui->assertPageAddress('/change-password');

        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @Then /^I am told that my password is invalid because it needs at least (.*)$/
     */
    public function iAmToldThatMyPasswordIsInvalidBecauseItNeedsAtLeast($reason)
    {
        $this->ui->assertPageAddress('/forgot-password/123456');

        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @Given /^I am unable to continue to reset my password$/
     */
    public function iAmUnableToContinueToResetMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @When /^I ask for a change of donors or attorneys details$/
     */
    public function iAskForAChangeOfDonorsOrAttorneysDetails()
    {
        $this->ui->assertPageAddress('/your-details');

        $this->ui->assertPageContainsText('Change a donor or attorney\'s details');
        $this->ui->clickLink('Change a donor or attorney\'s details');
    }

    /**
     * @When /^I ask for my password to be reset$/
     * @When /^I ask for my password to be reset with below correct (.*) and (.*) details$/
     */
    public function iAskForMyPasswordToBeReset(
        $email = 'test@example.com',
        $email_confirmation = 'test@example.com'
    ) {
        $this->ui->assertPageAddress('/forgot-password');

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => $this->userId,
                            'PasswordResetToken' => '123456',
                        ]
                    )
                )
            );

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('email_address', $params);
                    assertArrayHasKey('personalisation', $params);

                    assertInternalType('array', $params['personalisation']);
                    assertArrayHasKey('password-reset-url', $params['personalisation']);
                }
            );

        $this->ui->fillField('email', $email);
        $this->ui->fillField('email_confirm', $email_confirmation);
        $this->ui->pressButton('Email me the link');
    }

    /**
     * @When /^I ask for my password to be reset with below incorrect (.*) and (.*) details$/
     */
    public function iAskForMyPasswordToBeResetWithBelowInCorrectEmailAndConfirmationEmailDetails(
        $email,
        $email_confirmation
    ) {
        $this->ui->assertPageAddress('/forgot-password');

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_FORBIDDEN,
                    [],
                    json_encode([])
                )
            );

        $this->ui->fillField('email', $email);
        $this->ui->fillField('email_confirm', $email_confirmation);
        $this->ui->pressButton('Email me the link');
    }

    /**
     * @Given /^I ask to change my password$/
     */
    public function iAskToChangeMyPassword()
    {
        $session = $this->ui->getSession();
        $page = $session->getPage();

        $link = $page->find('css', 'a[href="change-password"]');
        if ($link === null) {
            throw new Exception('change password link not found');
        }

        $link->click();

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('change-password');

        $passwordInput = $page->find('css', 'input[type="password"]');

        if ($passwordInput === null) {
            throw new Exception('no password input box found');
        }
    }

    /**
     * @When /^I attempt to sign in again$/
     */
    public function iAttemptToSignInAgain()
    {
        // Dashboard page checks for all LPA's for a user
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->visit('/login');
    }

    /**
     * @Then /^I can change my email if required$/
     */
    public function iCanChangeMyEmailIfRequired()
    {
        $this->ui->assertPageAddress('/your-details');

        $this->ui->assertPageContainsText('Email address');
        $this->ui->assertPageContainsText($this->userEmail);

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $changeEmailText = 'Change email address';
        $link = $page->findLink($changeEmailText);
        if ($link === null) {
            throw new Exception($changeEmailText . ' link not found');
        }
    }

    /**
     * @Then /^I can change my passcode if required$/
     */
    public function iCanChangeMyPasscodeIfRequired()
    {
        $this->ui->assertPageAddress('/your-details');

        $this->ui->assertPageContainsText('Password');

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $changePasswordtext = 'Change password';
        $link = $page->findLink($changePasswordtext);
        if ($link === null) {
            throw new Exception($changePasswordtext . ' link not found');
        }
    }

    /**
     * @Then /^I can see the accessibility statement for the Use service$/
     */
    public function iCanSeeTheAccessibilityStatementForTheUseService()
    {
        $this->ui->assertPageContainsText('Accessibility statement for Use a lasting power of attorney');
    }

    /**
     * @Then /^I can see the actor privacy notice$/
     */
    public function iCanSeeTheActorPrivacyNotice()
    {
        $this->ui->assertPageAddress('/privacy-notice');
        $this->ui->assertPageContainsText('Privacy notice');
    }

    /**
     * @Then /^I can see the actor terms of use$/
     */
    public function iCanSeeTheActorTermsOfUse()
    {
        $this->ui->assertPageAddress('/terms-of-use');
        $this->ui->assertPageContainsText('Terms of use');
        $this->ui->assertPageContainsText('The service is for donors and attorneys on an LPA.');
    }

    /**
     * @Then /^I can see user accounts table$/
     */
    public function iCanSeeUserAccountsTable()
    {
        $this->ui->assertPageAddress('/stats');
        $this->ui->assertPageContainsText('Number of user accounts created and deleted');
    }

    /**
     * @Given /^I choose a new invalid password of "(.*)"$/
     */
    public function iChooseANewInvalid($password)
    {
        $this->ui->assertPageAddress('/forgot-password/123456');

        // API fixture for reset token check
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Id' => '123456'])));

        $this->ui->fillField('password', $password);
        $this->ui->pressButton('Change password');
    }

    /**
     * @Given /^I choose a new password$/
     */
    public function iChooseANewPassword()
    {
        $this->ui->assertPageAddress('/forgot-password/123456');

        // API fixture for reset token check
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Id' => '123456'])));

        // API fixture for password reset
        $this->apiFixtures->patch('/v1/complete-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Id' => '123456'])))
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('token', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        $this->ui->fillField('password', 'n3wPassWord');
        $this->ui->pressButton('Change password');
    }

    /**
     * @Given /^I choose a new (.*) from below$/
     */
    public function iChooseANewPasswordFromGiven($password)
    {
        // API call for password reset request
        $this->apiFixtures->patch('/v1/change-password')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_FORBIDDEN,
                    [],
                    json_encode([])
                )
            );

        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->fillField('new_password', $password);

        $this->ui->pressButton('Change password');
    }

    /**
     * @When /^I click the (.*) link on the page$/
     */
    public function iClickTheBackLinkOnThePage($backLink)
    {
        $this->ui->assertPageContainsText($backLink);
        $this->ui->clickLink($backLink);
    }

    /**
     * @When /^I click the I already have an account link$/
     */
    public function iClickTheIAlreadyHaveAnAccountLink()
    {
        $this->ui->clickLink('I already have an account');
    }

    /**
     * @When /^I click the link to verify my new email address$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddress()
    {
        // API fixture for email reset token check
        $this->apiFixtures->get('/v1/can-reset-email')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => $this->userId,
                        ]
                    )
                )
            );

        // API fixture to complete email change
        $this->apiFixtures->patch('/v1/complete-change-email')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->visit('/verify-new-email/' . $this->userEmailResetToken);
    }

    /**
     * @When /^I click the link to verify my new email address after my token has expired$/
     * @When /^I click an old link to verify my new email address containing a token that no longer exists$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddressAfterMyTokenHasExpired()
    {
        $this->userEmailResetToken = 'exp1r3dT0k3n';
        // API fixture for email reset token check
        $this->apiFixtures->get('/v1/can-reset-email')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_GONE,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('/verify-new-email/' . $this->userEmailResetToken);
    }

    /**
     * @Given /^I confirm that I want to delete my account$/
     */
    public function iConfirmThatIWantToDeleteMyAccount()
    {
        $this->ui->assertPageAddress('/confirm-delete-account');

        $this->apiFixtures->delete('/v1/delete-account/' . $this->userId)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => $this->userId,
                            'Email' => $this->userEmail,
                            'Password' => $this->userPassword,
                            'LastLogin' => null,
                        ]
                    )
                )
            );

        $this->ui->clickLink('Yes, continue deleting my account');
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
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => '123',
                            'Email' => $this->email,
                            'ActivationToken' => $this->activationToken,
                        ]
                    )
                )
            );

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $this->email);
        $this->ui->fillField('show_hide_password', $this->password);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
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
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_CONFLICT,
                    [],
                    json_encode(
                        [
                            'Email' => $this->email,
                            'ActivationToken' => $this->activationToken,
                        ]
                    )
                )
            );

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $this->email);
        $this->ui->fillField('show_hide_password', $this->password);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    /**
     * @When /^I create an account using with an email address that has been requested for reset$/
     */
    public function iCreateAnAccountUsingWithAnEmailAddressThatHasBeenRequestedForReset()
    {
        $this->userEmail = 'test@test.com';
        $this->userPassword = 'pa33W0rd!123';

        $this->ui->assertPageAddress('/create-account');

        // API call for creating an account
        $this->apiFixtures->post('/v1/user')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_CONFLICT,
                    [],
                    json_encode(
                        [
                            'message' => 'Another user has requested to change their email to ' . $this->userEmail,
                        ]
                    )
                )
            );

        // API call for Notify to warn user their email an attempt to use their email has been made
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                }
            );

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    /**
     * @When /^I create an account with a password of (.*)$/
     */
    public function iCreateAnAccountWithAPasswordOf($password)
    {
        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', 'a@b.com');
        $this->ui->fillField('show_hide_password', $password);

        $this->ui->pressButton('Create account');
    }

    /**
     * @Given /^I do not provide any options and continue$/
     */
    public function iDoNotProvideAnyOptionsAndContinue()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I enter correct credentials$/
     */
    public function iEnterCorrectCredentials()
    {
        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);

        if ($this->userActive) {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_OK,
                        [],
                        json_encode(
                            [
                                'Id' => $this->userId,
                                'Email' => $this->userEmail,
                                'LastLogin' => '2020-01-01',
                            ]
                        )
                    )
                );

            // Dashboard page checks for all LPA's for a user
            $this->apiFixtures->get('/v1/lpas')
                ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));
        } else {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(new Response(StatusCodeInterface::STATUS_UNAUTHORIZED, [], json_encode([])));
        }

        $this->ui->pressButton('Sign in');
    }

    /**
     * @When /^I enter correct email with '(.*)' and (.*) below$/
     */
    public function iEnterCorrectEmailWithEmailFormatAndPasswordBelow($email_format, $password)
    {
        $this->ui->fillField('email', $email_format);
        $this->ui->fillField('password', $password);

        if ($this->userActive) {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_OK,
                        [],
                        json_encode(
                            [
                                'Id' => $this->userId,
                                'Email' => $email_format,
                                'LastLogin' => '2020-01-01',
                            ]
                        )
                    )
                );

            // Dashboard page checks for all LPA's for a user
            $this->apiFixtures->get('/v1/lpas')
                ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));
        } else {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(new Response(StatusCodeInterface::STATUS_UNAUTHORIZED, [], json_encode([])));
        }

        $this->ui->assertPageContainsText('Sign in');
        $this->ui->pressButton('Sign in');
    }

    /**
     * @When /^I hack the CSRF value with '(.*)'$/
     */
    public function iEnterDetailsButHackTheCSRFTokenWith($csrfToken)
    {
        $this->ui->getSession()->getPage()->find('css', '#__csrf')->setValue($csrfToken);

        $this->ui->assertPageContainsText('Sign in');
        $this->ui->pressButton('Sign in');
    }

    /**
     * @When /^I enter incorrect login details with (.*) and (.*) below$/
     */
    public function iEnterInCorrectLoginDetailsWithEmailFormatAndPasswordBelow($emailFormat, $password)
    {
        $this->ui->fillField('email', $emailFormat);
        $this->ui->fillField('password', $password);

        // API call for authentication
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([])));

        $this->ui->pressButton('Sign in');
    }

    /**
     * @When I enter incorrect login email
     */
    public function iEnterIncorrectLoginEmail()
    {
        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', 'inoc0rrectPassword');

        // API call for authentication
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], json_encode([])));

        $this->ui->pressButton('Sign in');
    }

    /**
     * @When I enter incorrect login password
     */
    public function iEnterIncorrectLoginPassword()
    {
        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', 'inoc0rrectPassword');

        // API call for authentication
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([])));

        $this->ui->pressButton('Sign in');
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
     * @When /^I follow my unique instructions on how to reset my password$/
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->ui->visit('/forgot-password/123456');

        $this->ui->assertPageContainsText('Change your password');
    }

    /**
     * @When /^I follow the instructions on how to activate my account$/
     */
    public function iFollowTheInstructionsOnHowToActivateMyAccount()
    {
        $this->activationToken = 'abcd2345';
        $this->userEmail = 'a@b.com';
        // API fixture for reset token check
        $this->apiFixtures->patch('/v1/user-activation')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => '123',
                            'Email' => $this->userEmail,
                            'activation_token' => $this->activationToken,
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertEquals('abcd2345', $params['activation_token']);
                }
            );

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('personalisation', $params);
                    assertArrayHasKey('sign-in-url', $params['personalisation']);
                    assertContains('/login', $params['personalisation']['sign-in-url']);
                }
            );

        $this->ui->visit('/activate-account/' . $this->activationToken);
    }

    /**
     * @When /^I hack the request id of the CSRF value$/
     */
    public function iHackTheRequestIdOfTheCSRFValue()
    {
        $value = $this->ui->getSession()->getPage()->find('css', '#__csrf')->getValue();
        $separated = explode('-', $value);
        $separated[1] = 'youhazbeenhaaxed'; //this is the requestid.
        $hackedValue = implode('-', $separated);
        $this->iEnterDetailsButHackTheCSRFTokenWith($hackedValue);
    }

    /**
     * @When /^I hack the token of the CSRF value$/
     */
    public function iHackTheTokenOfTheCSRFValue()
    {
        $value = $this->ui->getSession()->getPage()->find('css', '#__csrf')->getValue();

        $separated = explode('-', $value);
        $separated[0] = 'youhazbeenhaaxed'; //this is the token part.
        $hackedValue = implode('-', $separated);

        $this->iEnterDetailsButHackTheCSRFTokenWith($hackedValue);
    }

    /**
     * @Given /^I have asked for my password to be reset$/
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        // API fixture for reset token check
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => '123456',
                        ]
                    )
                )
            );
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
     * @Given /^I have deleted my account$/
     */
    public function iHaveDeletedMyAccount()
    {
        $this->iAmOnTheYourDetailsPage();
        $this->iRequestToDeleteMyAccount();
        $this->iConfirmThatIWantToDeleteMyAccount();
    }

    /**
     * @Given /^I have forgotten my password$/
     */
    public function iHaveForgottenMyPassword()
    {
        $this->iAccessTheLoginForm();
        $this->ui->assertPageAddress('/login');

        $this->ui->clickLink('Forgotten your password?');
    }

    /**
     * @Given /^I have logged in previously$/
     */
    public function iHaveLoggedInPreviously()
    {
        // do all the steps to sign in
        $this->iAccessTheLoginForm();

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);

        if ($this->userActive) {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_OK,
                        [],
                        json_encode(
                            [
                                'Id' => $this->userId,
                                'Email' => $this->userEmail,
                                'LastLogin' => null,
                            ]
                        )
                    )
                );
        } else {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(new Response(StatusCodeInterface::STATUS_UNAUTHORIZED, [], json_encode([])));
        }

        $this->ui->pressButton('Sign in');

        $this->iAmSignedIn();
        $this->iLogoutOfTheApplication();
    }

    /**
     * @Given /^I have not activated my account$/
     */
    public function iHaveNotActivatedMyAccount()
    {
        $this->userActive = false;
    }

    /**
     * @When /^I have provided required information for account creation such as (.*)(.*)(.*)$/
     */
    public function iHaveProvidedRequiredInformationForAccountCreationSuchAs($email, $password, $terms)
    {
        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $email);
        $this->ui->fillField('show_hide_password', $password);
        if ($terms === 1) {
            $this->ui->checkOption('terms');
        }

        $this->ui->pressButton('Create account');
    }

    /**
     * @Given /^I have requested to change my email address$/
     */
    public function iHaveRequestedToChangeMyEmailAddress()
    {
        // Not needed for this context
    }

    /**
     * @When /^I logout of the application$/
     */
    public function iLogoutOfTheApplication()
    {
        $link = $this->ui->getSession()->getPage()->find('css', 'a[href="/logout"]');
        $link->click();
    }

    /**
     * @When /^I navigate to the actor cookies page$/
     */
    public function iNavigateToTheActorCookiesPage()
    {
        $this->ui->clickLink('cookie policy');
    }

    /**
     * @Given /^I provide my current password$/
     */
    public function iProvideMyCurrentPassword()
    {
        $this->ui->fillField('current_password', $this->userPassword);
    }

    /**
     * @When /^I provide my new password$/
     */
    public function iProvideMyNewPassword()
    {
        $newPassword = 'Password123';

        // API call for password reset request
        $this->apiFixtures->patch('/v1/change-password')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );


        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->fillField('new_password', $newPassword);

        $this->ui->pressButton('Change password');
    }

    /**
     * @When /^I provided incorrect current password$/
     */
    public function iProvidedIncorrectCurrentPassword()
    {
        $newPassword = 'Password123';

        // API call for password reset request
        $this->apiFixtures->patch('/v1/change-password')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_FORBIDDEN,
                    [],
                    json_encode([])
                )
            );

        $this->ui->fillField('current_password', 'wrongPassword');
        $this->ui->fillField('new_password', $newPassword);

        $this->ui->pressButton('Change password');
    }

    /**
     * @Then /^I receive unique instructions on how to activate my account$/
     */
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount()
    {
        $this->ui->assertPageAddress('/create-account-success');

        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->userEmail);

        assertInternalType('string', $this->activationToken);
        assertEquals(true, $this->apiFixtures->isEmpty());
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
     * @Then /^I receive unique instructions on how to reset my password to my provided (.*)$/
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPasswordToMyProvidedEmail($email)
    {
        $this->ui->assertPageAddress('/forgot-password');
        $this->ui->assertPageContainsText('emailed a link to ' . strtolower($email));
    }

    /**
     * @When /^I request login to my account that was deleted$/
     */
    public function iRequestLoginToMyAccountThatWasDeleted()
    {
        $this->ui->visit('/login');

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);

        // API call for authentication
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([])));

        $this->ui->pressButton('Sign in');
    }

    /**
     * @When /^I request to change my email to one that another user has an expired request for$/
     * @When /^I request to change my email to a unique email address$/
     */
    public function iRequestToChangeMyEmailToAUniqueEmailAddress()
    {
        $this->apiFixtures->patch('/v1/request-change-email')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'EmailResetExpiry' => time() + (60 * 60 * 48),
                            'Email' => $this->userEmail,
                            'LastLogin' => null,
                            'Id' => $this->userId,
                            'NewEmail' => $this->newUserEmail,
                            'EmailResetToken' => $this->userEmailResetToken,
                            'Password' => $this->userPassword,
                        ]
                    )
                )
            );

        // API call for Notify to current email
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('email_address', $params);
                    assertArrayHasKey('personalisation', $params);

                    assertInternalType('array', $params['personalisation']);
                    assertArrayHasKey('new-email-address', $params['personalisation']);
                }
            );

        // API call for Notify to new email
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('email_address', $params);
                    assertArrayHasKey('personalisation', $params);

                    assertInternalType('array', $params['personalisation']);
                    assertArrayHasKey('verify-new-email-url', $params['personalisation']);
                }
            );

        $this->ui->fillField('new_email_address', $this->newUserEmail);
        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->pressButton('Save new email address');
    }

    /**
     * @When /^I request to change my email to an email address that is taken by another user on the service$/
     * @When /^I request to change my email to one that another user has requested$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressThatIsTakenByAnotherUserOnTheService()
    {
        $this->apiFixtures->patch('/v1/request-change-email')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_CONFLICT, [], json_encode([]))
            )->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertInternalType('array', $params);
                    assertArrayHasKey('user-id', $params);
                    assertArrayHasKey('new-email', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        // API call for Notify to new email requested
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                }
            );

        $this->ui->fillField('new_email_address', $this->newUserEmail);
        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->pressButton('Save new email address');
    }

    /**
     * @When /^I request to change my email to an invalid email$/
     */
    public function iRequestToChangeMyEmailToAnInvalidEmail()
    {
        $this->ui->fillField('new_email_address', 'invalidEmail.com');
        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->pressButton('Save new email address');
    }

    /**
     * @When /^I request to change my email to the same email of my account currently$/
     */
    public function iRequestToChangeMyEmailToTheSameEmailOfMyAccountCurrently()
    {
        $this->ui->fillField('new_email_address', $this->userEmail);
        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->pressButton('Save new email address');
    }

    /**
     * @When /^I request to change my email with an incorrect password$/
     */
    public function iRequestToChangeMyEmailWithAnIncorrectPassword()
    {
        $this->apiFixtures->patch('/v1/request-change-email')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([]))
            )->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertInternalType('array', $params);
                    assertArrayHasKey('user-id', $params);
                    assertArrayHasKey('new-email', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        $this->ui->fillField('new_email_address', $this->newUserEmail);
        $this->ui->fillField('current_password', 'inC0rr3ct');
        $this->ui->pressButton('Save new email address');
    }

    /**
     * @When /^I request to create an account$/
     */
    public function iRequestToCreateAnAccount()
    {
        $this->ui->clickLink('Create account');
    }

    /**
     * @When /^I request to delete my account$/
     */
    public function iRequestToDeleteMyAccount()
    {
        $this->ui->assertPageAddress('/your-details');
        $this->ui->clickLink('Delete account');
    }

    /**
     * @When /^I request to go back to the terms of use page$/
     */
    public function iRequestToGoBackToTheSpecifiedPage()
    {
        $this->ui->clickLink('Back');
    }

    /**
     * @When /^I request to return to the your details page$/
     */
    public function iRequestToReturnToTheYourDetailsPage()
    {
        $this->ui->assertPageAddress('/confirm-delete-account');
        $this->ui->clickLink('No, return to my details');
    }

    /**
     * @When /^I request to see the actor privacy notice$/
     */
    public function iRequestToSeeTheActorPrivacyNoticePage()
    {
        $this->ui->clickLink('privacy notice');
    }

    /**
     * @When /^I request to see the actor terms of use$/
     */
    public function iRequestToSeeTheActorTermsOfUse()
    {
        $this->ui->clickLink('terms of use');
    }

    /**
     * @Given /^I select the option to create a new account$/
     */
    public function iSelectTheOptionToCreateNewAccount()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->fillField('triageEntry', 'no');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I select the option to sign in to my existing account$/
     */
    public function iSelectTheOptionToSignInToMyExistingAccount()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText('Use a lasting power of attorney');
        $this->ui->fillField('triageEntry', 'yes');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I should be able to login with my new email address$/
     */
    public function iShouldBeAbleToLoginWithMyNewEmailAddress()
    {
        $this->ui->assertPageAddress('/login');
        // Login test is not needed since we already have one
    }

    /**
     * @Then /^I should be sent an email to both my current and new email$/
     */
    public function iShouldBeSentAnEmailToBothMyCurrentAndNewEmail()
    {
        // Not needed for this context
    }

    /**
     * @When /^I should be taken to the (.*) page$/
     */
    public function iShouldBeTakenToThePreviousPage($page)
    {
        if ($page == 'triage') {
            $this->ui->assertPageAddress('/home');
        } elseif ($page == 'login') {
            $this->ui->assertPageAddress('/login');
        } elseif ($page == 'dashboard') {
            $this->ui->assertPageAddress('/lpa/dashboard');
        } elseif ($page == 'your details') {
            $this->ui->assertPageAddress('/your-details');
        } elseif ($page == 'add a lpa') {
            $this->ui->assertPageAddress('/lpa/add-details');
        } elseif ($page == 'add by code') {
            $this->ui->assertPageAddress('/lpa/add-by-code');
        }
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
     * @Then /^I should be told my request was successful and an email is sent to the chosen email address to warn the
     * user$/
     */
    public function iShouldBeToldMyRequestWasSuccessfulAndAnEmailIsSentToTheChosenEmailAddressToWarnTheUser()
    {
        $this->ui->assertPageContainsText('Updating your email address');
        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->newUserEmail);
    }

    /**
     * @Then /^I should be told that I could not change my email because my password is incorrect$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseMyPasswordIsIncorrect()
    {
        $this->ui->assertPageContainsText('The password you entered is incorrect');
    }

    /**
     * @Then /^I should be told that I could not change my email because the email is invalid$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseTheEmailIsInvalid()
    {
        $this->ui->assertPageContainsText('Enter an email address in the correct format, like name@example.com');
    }

    /**
     * @Then /^I should be told that I could not change my email because the email is the same as my current email$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseTheEmailIsTheSameAsMyCurrentEmail()
    {
        $this->ui->assertPageContainsText(
            'The new email address you entered is the same as your current email address. They must be different.'
        );
    }

    /**
     * @Then /^I should be told that my email could not be changed$/
     */
    public function iShouldBeToldThatMyEmailCouldNotBeChanged()
    {
        $this->ui->assertPageContainsText('We cannot change your email address');
    }

    /**
     * @Given /^I should be told that my request was successful$/
     */
    public function iShouldBeToldThatMyRequestWasSuccessful()
    {
        $this->ui->assertPageContainsText('Updating your email address');
        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->newUserEmail);
    }

    /**
     * @Then /^I should see relevant (.*) message$/
     */
    public function iShouldSeeRelevantErrorMessage($error)
    {
        $this->ui->assertPageAddress('/login');
        $this->ui->assertPageContainsText($error);
    }

    /**
     * @Then /^I should see the (.*) message$/
     */
    public function iShouldSeeTheErrorMessage($error)
    {
        $this->ui->assertPageAddress('/forgot-password');
        $this->ui->assertPageContainsText($error);
    }

    /**
     * @When /^I view my dashboard$/
     */
    public function iViewMyDashboard()
    {
        // Dashboard page checks for all LPA's for a user
        $request = $this->apiFixtures->get('/v1/lpas')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->setLastRequest($request);

        $this->ui->visit('/lpa/dashboard');
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
     * @Given /^I want to create a new account$/
     */
    public function iWantToCreateANewAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I want to ensure cookie attributes are set$/
     */
    public function iWantToEnsureCookieAttributesAreSet()
    {
        $session = $this->ui->getSession();

        // retrieving response headers:
        $cookies = $session->getResponseHeaders()['set-cookie'];

        foreach ($cookies as $value) {
            if (strstr($value, 'session')) {
                assertContains('secure', $value);
                assertContains('httponly', $value);
            } else {
                throw new Exception('Cookie named session not found in the response header');
            }
        }
    }

    /**
     * @Then /^My account email address should be reset$/
     */
    public function myAccountEmailAddressShouldBeReset()
    {
        // Not needed for this context
    }

    /**
     * @Then /^my account is activated and I receive a confirmation email$/
     */
    public function myAccountIsActivatedAndIReceiveAConfirmationEmail()
    {
        $this->ui->assertPageContainsText('Account activated');
        $this->ui->assertPageContainsText('sign in');
    }

    /**
     * @Then /^My account is deleted$/
     */
    public function myAccountIsDeleted()
    {
        // Not needed for this context
    }

    /**
     * @Given /^My email reset token is still valid$/
     */
    public function myEmailResetTokenIsStillValid()
    {
        $this->userEmailResetToken = '12345abcde';
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
}
